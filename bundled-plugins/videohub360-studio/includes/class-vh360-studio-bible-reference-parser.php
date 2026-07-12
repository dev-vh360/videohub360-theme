<?php
if ( ! defined( 'ABSPATH' ) ) { exit; }
class VH360_Studio_Bible_Reference_Parser {
    public function parse( $reference ) {
        $reference = trim( str_replace( '–', '-', (string) $reference ) );
        if ( ! preg_match( '/^(.+?)\s+(\d+)(?::(\d+[a-z]?)(?:-(?:(\d+):)?(\d+[a-z]?))?)?$/i', $reference, $m ) ) {
            return new WP_Error( 'vh360_bible_reference_invalid', __( 'Reference could not be resolved.', 'videohub360-studio' ), array( 'status' => 400 ) );
        }
        $book = VH360_Studio_Bible_Books::normalize_name( $m[1] );
        if ( ! $book ) { return new WP_Error( 'vh360_bible_book_unknown', __( 'Unknown Bible book.', 'videohub360-studio' ), array( 'status' => 400 ) ); }
        $sc = absint( $m[2] ); $start = $this->parse_verse( isset( $m[3] ) && '' !== $m[3] ? $m[3] : '1' ); $sv = $start['verse'];
        $ec = isset( $m[4] ) && '' !== $m[4] ? absint( $m[4] ) : $sc;
        $end = isset( $m[5] ) && '' !== $m[5] ? $this->parse_verse( $m[5] ) : null; $ev = $end ? $end['verse'] : ( isset( $m[3] ) && '' !== $m[3] ? $sv : null );
        $ss = $start['suffix']; $es = $end ? $end['suffix'] : ( null === $ev ? '' : $ss );
        if ( $sc < 1 || $sv < 1 || $ec < 1 || ( null !== $ev && $ev < 1 ) || $ec < $sc || ( $ec === $sc && null !== $ev && ( $ev < $sv || ( $ev === $sv && strcmp( $es, $ss ) < 0 ) ) ) ) {
            return new WP_Error( 'vh360_bible_reference_reversed', __( 'Invalid Bible reference range.', 'videohub360-studio' ), array( 'status' => 400 ) );
        }
        return array( array( 'bookKey' => $book['key'], 'startChapter' => $sc, 'startVerse' => $sv, 'startVerseSuffix' => $ss, 'endChapter' => $ec, 'endVerse' => $ev, 'endVerseSuffix' => $es ) );
    }

    private function parse_verse( $value ) {
        preg_match( '/^(\d+)([a-z]?)$/i', trim( (string) $value ), $m );
        return array( 'verse' => $m ? absint( $m[1] ) : 0, 'suffix' => $m ? strtolower( $m[2] ) : '' );
    }
}
