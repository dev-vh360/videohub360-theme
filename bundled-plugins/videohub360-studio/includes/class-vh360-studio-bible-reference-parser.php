<?php
if ( ! defined( 'ABSPATH' ) ) { exit; }
class VH360_Studio_Bible_Reference_Parser {
    public function parse( $reference ) {
        $reference=trim(str_replace('–','-',(string)$reference));
        if(!preg_match('/^(.+?)\s+(\d+)(?::(\d+[a-z]?)(?:-(?:(\d+):)?(\d+[a-z]?))?)?$/i',$reference,$m)){return new WP_Error('vh360_bible_reference_invalid',__('Reference could not be resolved.','videohub360-studio'),array('status'=>400));}
        $book=VH360_Studio_Bible_Books::normalize_name($m[1]); if(!$book){return new WP_Error('vh360_bible_book_unknown',__('Unknown Bible book.','videohub360-studio'),array('status'=>400));}
        $sc=absint($m[2]); $sv=isset($m[3])&&$m[3]!==''?absint($m[3]):1; $ec=isset($m[4])&&$m[4]!==''?absint($m[4]):$sc; $ev=isset($m[5])&&$m[5]!==''?absint($m[5]):(isset($m[3])&&$m[3]!==''?$sv:999);
        if($sc<1||$sv<1||$ec<1||$ev<1||$ec<$sc||($ec===$sc&&$ev<$sv)){return new WP_Error('vh360_bible_reference_reversed',__('Invalid Bible reference range.','videohub360-studio'),array('status'=>400));}
        return array(array('book_key'=>$book['key'],'start_chapter'=>$sc,'start_verse'=>$sv,'end_chapter'=>$ec,'end_verse'=>$ev));
    }
}
