<?php

namespace App\Testing;

use Exception;
use Facebook\WebDriver\Remote\RemoteWebElement;
use Laravel\Dusk\ElementResolver;
use Facebook\WebDriver\WebDriverBy;
use Log;
use Str;

class DynamicElementResolver extends ElementResolver
{
    /**
     * Find an element by the given selector or throw an exception.
     *
     * @param  string  $selector
     * @return RemoteWebElement
     */
    public function findOrFail($selector): RemoteWebElement
    {
        if (Str::startsWith($selector, 'xpath[')) {
            $xpath = Str::between($selector, 'xpath[', ']');
            $selector = WebDriverBy::xpath($xpath);
        } else {
            $selector = WebDriverBy::cssSelector($this->format($selector));
        }
        return $this->driver->findElement($selector);
    }

    /**
     * Find the elements by the given selector or return an empty array.
     *
     * @param  string  $selector
     * @return array
     */
    public function all($selector)
    {
        try {
            return $this->driver->findElements(
                (is_string($selector)) ? WebDriverBy::cssSelector($this->format($selector)) : $selector
            );
        } catch (Exception $e) {
            Log::channel('daily')->error($e->getMessage());
        }

        return [];
    }
}