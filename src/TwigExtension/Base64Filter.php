<?php

namespace App\TwigExtension;

use Twig\TwigFilter;

class Base64Filter extends \Twig_Extension
{
    public function getFilters() { 
        return array( 
            new TwigFilter('base64_encode', array($this, 'base64Encode')),
            new TwigFilter('base64_decode', array($this, 'base64Decode'))
        );
    }
     
    public function base64Encode($input)
    {
        return base64_encode($input);
    }
    
    public function base64Decode($input)
    {
        return base64_decode($input);
    }
}