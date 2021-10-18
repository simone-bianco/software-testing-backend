<?php

namespace App\Testing;

use Facebook\WebDriver\Exception\TimeOutException;
use Facebook\WebDriver\Remote\RemoteWebDriver;
use Facebook\WebDriver\Remote\RemoteWebElement;
use Facebook\WebDriver\WebDriverBy;
use Laravel\Dusk\Browser;
use Laravel\Dusk\ElementResolver;

class XPathBrowsing extends Browser {

    /**
     * Create a browser instance.
     *
     * @param  RemoteWebDriver  $driver
     * @param  ElementResolver  $resolver
     * @return void
     */
    public function __construct($driver, $resolver = null)
    {
        parent::__construct($driver, $resolver = null);
        $this->driver = $driver;

        $this->resolver = $resolver ?: new DynamicElementResolver($driver);
    }

    /**
     * Find an element by the given selector or return null.
     *
     * @param  string|WebDriverBy  $selector
     * @return RemoteWebElement|null
     */
    public function findBySelector($selector): ?RemoteWebElement
    {
        return $this->resolver->find($selector);
    }
}
