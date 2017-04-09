<?php
/**
 * Faker.php
 * Part of arid-grazer
 *
 * @author: Marlon
 *
 */

namespace App\Library;

use Faker\Factory as fkr;

class Faker
{

    private $faker;

    public function __construct()
    {
    }

    private function get()
    {
        if (!$this->faker) {
            $this->faker = fkr::create('-ZA');
        }
        return $this->faker;
    }

    /**
     * Color, useful as a pre.
     * @return string
     */
    public function color() : string
    {
        return $this->get()->safeColorName;
    }

    /**
     * Firstname, useful as a post
     * @return string
     */
    public function name() : string
    {
        return $this->get()->firstName;
    }


    public function job() : string
    {
        return $this->get()->jobTitle;
    }

    public function domain() : string
    {
        return $this->get()->domainWord;
    }

    public function wordsTwo() : string
    {
        return $this->get()->words(2, true);
    }

    public function street() : string
    {
        return $this->get()->streetName;
    }

    public function company() : string
    {
        return $this->get()->company;
    }

    public function customs() : string
    {
        $customs = ['superb', 'explosive', 'volatile', 'shitty'];
        $key = array_rand($customs);
        return strval($customs[$key]);
    }

    public function adjective() : string
    {
        $adj = [
            'abject', 'outstanding', 'obscene', 'debonair', 'steady', 'poised', 'colossal', 'literate',
            'helpful', 'perpetual', 'smelly', 'whimsical', 'faithful', 'berserk', 'electric', 'shy', 'subsequent',
            'alert', 'spiritual', 'shivering', 'broad', 'redundant', 'abaft', 'obsequious', 'magical', 'numerous',
            'beneficial', 'salty', 'hypnotic', 'prickly', 'rotten', 'able', 'early', 'lovely', 'ludicrous',
            'damaged', 'shaggy', 'windy', 'eight', 'courageous', 'fluffy', 'right', 'aboard', 'confused',
            'overwrought', 'festive', 'excellent', 'big', 'invincible', 'lethal', 'steep', 'sleepy', 'moldy',
            'shut', 'selfish', 'racial', 'beautiful', 'tightfisted', 'raspy', 'careful', 'disagreeable',
            'frightened', 'defeated', 'uneven', 'bored', 'abiding', 'good', 'famous', 'hospitable', 'meek', 'cool',
            'maddening', 'purple', 'vast', 'smart', 'puffy', 'lush', 'unruly', 'fine', 'tasty', 'shaky',
            'chivalrous', 'available', 'graceful', 'dear', 'obtainable', 'natural', 'ahead', 'useful', 'accurate',
            'curved', 'expensive', 'unused', 'bewildered', 'nutty', 'lively', 'yummy', 'holistic', 'cruel',
            'grateful', 'strange', 'tiny', 'efficacious', 'simple', 'combative', 'symptomatic', 'fragile', 'wary',
            'plain', 'curvy', 'thundering', 'incandescent', 'uttermost', 'snotty', 'pretty', 'null', 'elderly',
            'delicious', 'two', 'exotic', 'reflective', 'dangerous', 'wet', 'familiar', 'furtive', 'stupid',
            'alcoholic', 'heartbreaking',
            'incandescent', 'uttermost', 'snotty', 'pretty', 'null', 'elderly', 'delicious', 'two', 'exotic',
            'reflective', 'dangerous', 'wet', 'familiar', 'furtive', 'stupid', 'alcoholic', 'heartbreaking',
            'festive', 'petite', 'overt', 'freezing', 'tangy', 'terrific', 'flimsy', 'little',
            'aquatic', 'milky', 'ajar', 'distinct', 'sable', 'imported', 'shy', 'noisy', 'nimble', 'lively', 'hurried',
            'judicious', 'impossible', 'dangerous', 'reminiscent', 'graceful', 'obeisant', 'picayune', 'omniscient',
            'sad', 'insidious', 'hushed', 'spurious', 'abstracted', 'plucky', 'silent', 'gruesome', 'kind', 'telling',
            'near', 'wasteful', 'understood', 'plastic', 'dry', 'responsible', 'aromatic', 'magical', 'fearless',
            'jittery', 'first', 'tough', 'easy', 'dependent', 'rural', 'squealing', 'threatening', 'puffy', 'grubby',
            'placid', 'orange', 'jaded', 'spicy', 'efficacious', 'acid', 'striped', 'brainy', 'pricey', 'cheerful',
            'workable', 'black', 'optimal', 'chief', 'embarrassed', 'physical', 'humorous', 'rare', 'dynamic',
            'sloppy', 'extra-small', 'bouncy', 'silky', 'bitter', 'boorish', 'abnormal', 'abaft', 'hot', 'lazy',
            'worried', 'willing', 'dysfunctional', 'bite-sized', 'profuse', 'deadpan', 'careful', 'rambunctious',
            'meaty', 'big', 'stiff', 'momentous', 'complex', 'faint', 'clear', 'melted', 'lamentable', 'useful',
            'filthy', 'inexpensive', 'different', 'shaky', 'ablaze', 'deep', 'flowery', 'ordinary', 'small',
            'obedient', 'common', 'shut', 'dull', 'elastic', 'mountainous', 'curly', 'zonked', 'painful',
            'numerous', 'guiltless', 'skinny', 'available', 'lavish', 'disagreeable'
        ];
        $key = array_rand($adj);
        return strval($adj[$key]);
    }

    /**
     * Returns a very random, prepared, lowercase Prefix part.
     * @return string
     */
    public function randomPre() : string
    {
        $preGens = ['color', 'adjective'];
        $randomkey = array_rand($preGens);
        $randomGen = $preGens[$randomkey];
        return str_replace(' ', '-', strtolower($this->{strval($randomGen)}()));
    }

    /**
     * Returns a very random, prepared, lowercase Postfix part.
     * @return string
     */
    public function randomPost() : string
    {
        $postGens = ['name', 'job'];
        $randomkey = array_rand($postGens);
        $randomGen = $postGens[$randomkey];
        return str_replace(' ', '-', strtolower($this->{strval($randomGen)}()));
    }
}