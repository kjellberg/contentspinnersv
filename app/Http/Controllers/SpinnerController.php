<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use App\Http\Requests;

use Stichoza\GoogleTranslate\TranslateClient;

class SpinnerController extends Controller
{
    public function ajaxCall(Request $req) {

    	if (!isset($req->text) or empty($req->text))
    		return false;

    	$gt = new TranslateClient();

    	$wordslist = explode(" ", $req->text);

    	$translatedlist = [];

    	foreach ($wordslist as $word) {
    		$translatedlist[] = $this->replaceWord($word);
    	}

    	$generated_text = implode(" ", $translatedlist);

    	$english_translation = $gt->setSource('sv')->setTarget('en')->translate($generated_text);
    	$swedish_translation = $gt->setSource('en')->setTarget('sv')->translate($english_translation);

    	return $swedish_translation;
    }

    public function replaceWord( $word ) {

    	if (strlen($word) < 4)
    		return $word;

    	$synlist = \Cache::remember('swedish_synpairs', 60*24*30, function() {

		    $xml_file_path = storage_path("swedish_synpairs.xml");
		    $xml = \XmlParser::load($xml_file_path);

	    	return $xml->parse([
			    'synonyms' => ['uses' => 'synonyms.syn[w1,w2]'],
			]);

		});

		$matching_words = array_filter(
		    $synlist['synonyms'],
		    function ($e) use($word) {
		        return $e['w1'] == $word;
		    }
		);

		if (empty($matching_words))
			return $word;

		$replacement_word = $matching_words[array_rand($matching_words)];

		return $replacement_word['w2'];
    }
}
