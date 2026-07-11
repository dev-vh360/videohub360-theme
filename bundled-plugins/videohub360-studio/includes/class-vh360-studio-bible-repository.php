<?php
if ( ! defined( 'ABSPATH' ) ) { exit; }
class VH360_Studio_Bible_Repository {
    const LIMIT = 50;
    const MAX_CHAPTER_SPAN = 5;

    public function list_translations() {
        global $wpdb; $t = VH360_Studio_Database::bible_translations_table_name();
        return $wpdb->get_results( $wpdb->prepare( "SELECT translation_key AS translationKey,name,abbreviation,language,source_name AS sourceName,source_url AS sourceUrl,license_name AS licenseName,license_url AS licenseUrl,attribution,attribution_required AS attributionRequired,dataset_version AS datasetVersion,source_hash AS sourceHash,verse_count AS verseCount,book_count AS bookCount,imported_at AS importedAt,status FROM {$t} WHERE status=%s ORDER BY name", 'installed' ), ARRAY_A );
    }

    public function list_all_translations() {
        global $wpdb; $t = VH360_Studio_Database::bible_translations_table_name();
        return $wpdb->get_results( "SELECT translation_key AS translationKey,name,abbreviation,language,status,source_name AS sourceName,license_name AS licenseName,attribution_required AS attributionRequired,dataset_version AS datasetVersion,source_hash AS sourceHash,verse_count AS verseCount,book_count AS bookCount,imported_at AS importedAt FROM {$t} ORDER BY name", ARRAY_A ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
    }

    public function translation_exists( $key ) { return (bool) $this->get_translation( $key ); }

    public function get_translation( $key ) {
        global $wpdb; $t = VH360_Studio_Database::bible_translations_table_name();
        return $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$t} WHERE translation_key=%s AND status='installed'", sanitize_key( $key ) ), ARRAY_A );
    }

    private function require_translation( $key ) {
        $tr = $this->get_translation( $key );
        if ( ! $tr ) { return new WP_Error( 'vh360_bible_translation_missing', __( 'This translation is no longer installed.', 'videohub360-studio' ), array( 'status' => 404 ) ); }
        return $tr;
    }

    public function list_books( $translation_key ) {
        $tr = $this->require_translation( $translation_key ); if ( is_wp_error( $tr ) ) { return $tr; }
        global $wpdb; $v = VH360_Studio_Database::bible_verses_table_name();
        return $wpdb->get_results( $wpdb->prepare( "SELECT book_key AS bookKey, MIN(book_name) AS name, MIN(book_order) AS bookOrder, COUNT(*) AS verseRows FROM {$v} WHERE translation_key=%s GROUP BY book_key ORDER BY bookOrder", sanitize_key( $translation_key ) ), ARRAY_A );
    }

    public function list_chapters( $translation_key, $book_key ) {
        $tr = $this->require_translation( $translation_key ); if ( is_wp_error( $tr ) ) { return $tr; }
        global $wpdb; $v = VH360_Studio_Database::bible_verses_table_name();
        return array_map( 'absint', $wpdb->get_col( $wpdb->prepare( "SELECT DISTINCT chapter_number FROM {$v} WHERE translation_key=%s AND book_key=%s ORDER BY chapter_number", sanitize_key( $translation_key ), strtoupper( sanitize_text_field( $book_key ) ) ) ) );
    }

    public function get_chapter( $translation_key, $book_key, $chapter ) {
        $tr = $this->require_translation( $translation_key ); if ( is_wp_error( $tr ) ) { return $tr; }
        $book_key = strtoupper( sanitize_text_field( $book_key ) ); $chapter = absint( $chapter );
        $key = 'vh360_bible_' . sanitize_key( $translation_key ) . '_' . $book_key . '_' . $chapter . '_' . $tr['source_hash'];
        $cached = wp_cache_get( $key, 'vh360_studio_bible' ); if ( false !== $cached ) { return $cached; }
        global $wpdb; $v = VH360_Studio_Database::bible_verses_table_name();
        $rows = $wpdb->get_results( $wpdb->prepare( "SELECT book_key AS bookKey,book_name AS bookName,chapter_number AS chapter,verse_number AS verse,verse_suffix AS suffix,verse_text AS text,verse_status AS status FROM {$v} WHERE translation_key=%s AND book_key=%s AND chapter_number=%d ORDER BY verse_number,verse_suffix", sanitize_key( $translation_key ), $book_key, $chapter ), ARRAY_A );
        $rows = array_values( array_filter( $rows, function( $r ) { return 'present' === $r['status'] && '' !== trim( $r['text'] ); } ) );
        wp_cache_set( $key, $rows, 'vh360_studio_bible', HOUR_IN_SECONDS );
        return $rows;
    }

    public function get_passage( $translation_key, $ranges ) {
        $tr = $this->require_translation( $translation_key ); if ( is_wp_error( $tr ) ) { return $tr; }
        $out = array();
        foreach ( $ranges as $range ) {
            $range = $this->normalize_range( $range );
            $valid = $this->validate_range_bounds( $translation_key, $range ); if ( is_wp_error( $valid ) ) { return $valid; }
            for ( $c = $range['startChapter']; $c <= $range['endChapter']; $c++ ) {
                $chapter = $this->get_chapter( $translation_key, $range['bookKey'], $c ); if ( is_wp_error( $chapter ) ) { return $chapter; }
                foreach ( $chapter as $row ) {
                    $v = absint( $row['verse'] );
                    if ( ( $c === $range['startChapter'] && $v < $range['startVerse'] ) || ( $c === $range['endChapter'] && $v > $range['endVerse'] ) ) { continue; }
                    $out[] = $row;
                    if ( count( $out ) > self::LIMIT ) { return new WP_Error( 'vh360_bible_passage_too_large', __( 'Bible passages are limited to 50 verses.', 'videohub360-studio' ), array( 'status' => 400 ) ); }
                }
            }
        }
        return $out;
    }

    public function resolve_reference( $translation_key, $reference ) {
        $parser = new VH360_Studio_Bible_Reference_Parser(); $ranges = $parser->parse( $reference ); if ( is_wp_error( $ranges ) ) { return $ranges; }
        foreach ( $ranges as &$range ) { $range = $this->normalize_range( $range ); $range = $this->complete_range( $translation_key, $range ); if ( is_wp_error( $range ) ) { return $range; } }
        $verses = $this->get_passage( $translation_key, $ranges ); if ( is_wp_error( $verses ) ) { return $verses; }
        if ( empty( $verses ) ) { return new WP_Error( 'vh360_bible_reference_empty', __( 'Reference could not be resolved.', 'videohub360-studio' ), array( 'status' => 404 ) ); }
        $tr = $this->get_translation( $translation_key ); $book = VH360_Studio_Bible_Books::get( $ranges[0]['bookKey'] );
        return array(
            'translation' => array( 'translationKey' => $tr['translation_key'], 'translationLabel' => $tr['abbreviation'], 'datasetVersion' => $tr['dataset_version'], 'sourceHash' => $tr['source_hash'], 'attribution' => $tr['attribution'], 'attributionRequired' => (bool) $tr['attribution_required'] ),
            'reference'   => $this->display_reference( $book['default_name'], $ranges[0] ),
            'ranges'      => $ranges,
            'verses'      => $verses,
        );
    }

    private function normalize_range( $r ) {
        return array(
            'bookKey' => strtoupper( sanitize_text_field( isset( $r['bookKey'] ) ? $r['bookKey'] : ( isset( $r['book_key'] ) ? $r['book_key'] : '' ) ) ),
            'startChapter' => absint( isset( $r['startChapter'] ) ? $r['startChapter'] : ( isset( $r['start_chapter'] ) ? $r['start_chapter'] : 0 ) ),
            'startVerse' => absint( isset( $r['startVerse'] ) ? $r['startVerse'] : ( isset( $r['start_verse'] ) ? $r['start_verse'] : 0 ) ),
            'endChapter' => absint( isset( $r['endChapter'] ) ? $r['endChapter'] : ( isset( $r['end_chapter'] ) ? $r['end_chapter'] : 0 ) ),
            'endVerse' => isset( $r['endVerse'] ) ? ( null === $r['endVerse'] ? null : absint( $r['endVerse'] ) ) : ( isset( $r['end_verse'] ) ? absint( $r['end_verse'] ) : 0 ),
        );
    }

    private function complete_range( $translation_key, $range ) {
        if ( null !== $range['endVerse'] && $range['endVerse'] > 0 ) { return $range; }
        global $wpdb; $v = VH360_Studio_Database::bible_verses_table_name();
        $end = (int) $wpdb->get_var( $wpdb->prepare( "SELECT MAX(verse_number) FROM {$v} WHERE translation_key=%s AND book_key=%s AND chapter_number=%d AND verse_status='present'", sanitize_key( $translation_key ), $range['bookKey'], $range['endChapter'] ) );
        if ( $end < 1 ) { return new WP_Error( 'vh360_bible_reference_out_of_range', __( 'Reference could not be resolved.', 'videohub360-studio' ), array( 'status' => 400 ) ); }
        $range['endVerse'] = $end; return $range;
    }

    private function validate_range_bounds( $translation_key, $range ) {
        if ( ! VH360_Studio_Bible_Books::get( strtoupper( $range['bookKey'] ) ) && ! VH360_Studio_Bible_Books::get( $range['bookKey'] ) ) { return new WP_Error( 'vh360_bible_book_unknown', __( 'Unknown Bible book.', 'videohub360-studio' ), array( 'status' => 400 ) ); }
        if ( $range['endChapter'] < $range['startChapter'] || $range['endChapter'] - $range['startChapter'] > self::MAX_CHAPTER_SPAN ) { return new WP_Error( 'vh360_bible_reference_too_large', __( 'Bible reference spans too many chapters.', 'videohub360-studio' ), array( 'status' => 400 ) ); }
        global $wpdb; $v = VH360_Studio_Database::bible_verses_table_name();
        $start = (int) $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM {$v} WHERE translation_key=%s AND book_key=%s AND chapter_number=%d AND verse_number=%d AND verse_status='present'", sanitize_key( $translation_key ), $range['bookKey'], $range['startChapter'], $range['startVerse'] ) );
        $end = (int) $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM {$v} WHERE translation_key=%s AND book_key=%s AND chapter_number=%d AND verse_number=%d AND verse_status='present'", sanitize_key( $translation_key ), $range['bookKey'], $range['endChapter'], $range['endVerse'] ) );
        if ( ! $start || ! $end ) { return new WP_Error( 'vh360_bible_reference_out_of_range', __( 'Reference could not be resolved.', 'videohub360-studio' ), array( 'status' => 400 ) ); }
        return true;
    }

    private function display_reference( $book, $r ) {
        $same = $r['startChapter'] === $r['endChapter'];
        if ( $same && $r['startVerse'] === $r['endVerse'] ) { return $book . ' ' . $r['startChapter'] . ':' . $r['startVerse']; }
        return $book . ' ' . $r['startChapter'] . ':' . $r['startVerse'] . '–' . ( $same ? '' : $r['endChapter'] . ':' ) . $r['endVerse'];
    }



    public function set_translation_status( $translation_key, $status ) {
        if ( ! in_array( $status, array( 'installed', 'disabled' ), true ) ) { return new WP_Error( 'vh360_bible_status_invalid', __( 'Invalid translation status.', 'videohub360-studio' ), array( 'status' => 400 ) ); }
        global $wpdb; $t = VH360_Studio_Database::bible_translations_table_name();
        $key = sanitize_key( $translation_key );
        $exists = (int) $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM {$t} WHERE translation_key=%s", $key ) );
        if ( ! $exists ) { return new WP_Error( 'vh360_bible_translation_missing', __( 'This translation is no longer installed.', 'videohub360-studio' ), array( 'status' => 404 ) ); }
        $updated = $wpdb->update( $t, array( 'status' => $status, 'updated_at' => current_time( 'mysql' ) ), array( 'translation_key' => $key ) );
        return false === $updated ? new WP_Error( 'vh360_bible_status_failed', __( 'Translation status could not be updated.', 'videohub360-studio' ), array( 'status' => 500 ) ) : true;
    }

    public function delete_translation( $translation_key ) {
        global $wpdb; $t = VH360_Studio_Database::bible_translations_table_name(); $v = VH360_Studio_Database::bible_verses_table_name(); $key = sanitize_key( $translation_key );
        $exists = (int) $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM {$t} WHERE translation_key=%s", $key ) );
        if ( ! $exists ) { return new WP_Error( 'vh360_bible_translation_missing', __( 'This translation is no longer installed.', 'videohub360-studio' ), array( 'status' => 404 ) ); }
        if ( false === $wpdb->query( 'START TRANSACTION' ) ) { return new WP_Error( 'vh360_bible_delete_failed', __( 'Translation could not be deleted.', 'videohub360-studio' ), array( 'status' => 500 ) ); }
        if ( false === $wpdb->delete( $v, array( 'translation_key' => $key ) ) || false === $wpdb->delete( $t, array( 'translation_key' => $key ) ) ) { $wpdb->query( 'ROLLBACK' ); return new WP_Error( 'vh360_bible_delete_failed', __( 'Translation could not be deleted.', 'videohub360-studio' ), array( 'status' => 500 ) ); }
        if ( false === $wpdb->query( 'COMMIT' ) ) { return new WP_Error( 'vh360_bible_delete_failed', __( 'Translation could not be deleted.', 'videohub360-studio' ), array( 'status' => 500 ) ); }
        self::clear_translation_cache( $key ); return true;
    }

    public static function clear_translation_cache( $translation_key ) { wp_cache_set( 'vh360_bible_cache_bust_' . sanitize_key( $translation_key ), time(), 'vh360_studio_bible' ); }
}
