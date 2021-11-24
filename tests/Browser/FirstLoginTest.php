<?php

namespace Tests\Browser;

use App\Models\Responsible;
use Facebook\WebDriver\Remote\RemoteWebDriver;
use Facebook\WebDriver\Remote\RemoteWebElement;
use Facebook\WebDriver\WebDriverBy;
use Laravel\Dusk\Browser;
use PragmaRX\Google2FA\Google2FA;
use Tests\BackofficeDuskTestCase;
use Throwable;

class FirstLoginTest extends BackofficeDuskTestCase
{
    public function setUp(): void
    {
        parent::setUp();
    }

    /**
     * @param  string|string[]  $text
     * @param RemoteWebDriver|RemoteWebElement $driver
     * @return RemoteWebElement|null
     */
    protected function getButtonByText($text, $driver): ?RemoteWebElement
    {
        if (!is_array($text)) {
            $text = array($text);
        }
        $buttons = $driver->findElements(WebDriverBy::tagName('button'));
        foreach ($buttons as $button) {
            try {
                $buttonText = $button->findElement(WebDriverBy::tagName('span'))->getText();
                if (in_array(strtolower($buttonText), $text)) {
                    return $button;
                }
            } catch (Throwable $exception) {
            }
        }
        return null;
    }

    /**
     * @param  string|string[]  $text
     * @param RemoteWebDriver|RemoteWebElement $driver
     * @return RemoteWebElement|null
     */
    protected function getInputByLabelText($text, $driver): ?RemoteWebElement
    {
        if (!is_array($text)) {
            $text = array($text);
        }
        # Prendo gli input text
        $inputs = $driver->findElements(WebDriverBy::tagName('input'));
        foreach ($inputs as $input) {
            try {
                $inputId = $input->getAttribute('id');
                $label = $driver->findElement(WebDriverBy::xpath("(//label[@for='$inputId'])[1]"));
                $labelText = strtolower($label->getText());
                if (in_array($labelText, $text)) {
                    return $input;
                }
            } catch (Throwable $exception) {
            }
        }
        return null;
    }

    /**
     * Quando viene effettuato il login per la prima volta si viene reindirizzati sulla pagina
     * della 2FA
     *
     * @throws Throwable
     */
    public function testFirstLogin()
    {
        $responsible = Responsible::first();
        $this->assertNotNull($responsible);
        $this->browse(function (Browser $browser) use ($responsible) {
            $browser->visit('/login')
                ->waitForLocation('/login')
                ->assertSee('Accesso Backoffice');
            $browser->screenshot('firstLoginTest/1.png');
            //cerco il form del login
            $loginForm = $browser->driver->findElement(WebDriverBy::tagName('form'));


            //prendo gli input del form
            $inputs = $loginForm->findElements(WebDriverBy::tagName('input'));
            //inserimento email e password
            $emailInput = $this->getInputByLabelText(['email', 'user', 'username'], $loginForm);
            $this->assertNotNull($emailInput);
            $passwordInput = $this->getInputByLabelText('password', $loginForm);
            $this->assertNotNull($passwordInput);

            $emailInput->sendKeys($responsible->email);
            $passwordInput->sendKeys('test');

            $browser->screenshot('firstLoginTest/2.png');

            $loginButton = $this->getButtonByText(['login', 'accedi'], $loginForm);
            $this->assertNotNull($loginButton);

            //click su login
            $loginButton->click();

            //aspetto che si carichi la pagina
            $browser->waitForLocation('/qr/register');
            $browser->screenshot('firstLoginTest/3.png');
            //prendo la secret key generata
            $secret = $browser->element('div[class=v-card__text]')->getText();
            $google2fa = new Google2FA();
            //ricavo l'otp, come se avessi usato l'applicazione di google
            $currentOtp = $google2fa->getCurrentOtp($secret);
            //scrivo l'otp nell'input
            $insertOtpInput = $this->getInputByLabelText('codice generato', $browser->driver);
            $this->assertNotNull($insertOtpInput);
            $insertOtpInput->sendKeys($currentOtp);

            $browser->screenshot('firstLoginTest/4.png');

            //click su conferma
            $confirmOtp = $this->getButtonByText(['completa registrazione', 'conferma'], $browser->driver);
            $this->assertNotNull($confirmOtp);
            $confirmOtp->click();

            $browser->screenshot('firstLoginTest/5.png');
            //aspetto che si carichi
            $browser->waitForLocation('/qr/authenticate')
                ->assertSee('Inserisci Codice OTP');
            $browser->screenshot('firstLoginTest/6.png');
            //ricalcolo l'otp, in quanto il precedente potrebbe essere scaduto
            $currentOtp = $google2fa->getCurrentOtp($secret);

            //inserisco l'otp nell'input text
            $insertOtpInput = $this->getInputByLabelText('codice generato', $browser->driver);
            $this->assertNotNull($insertOtpInput);
            $insertOtpInput->sendKeys($currentOtp);

            $browser->screenshot('firstLoginTest/7.png');
            //click su conferma
            $confirmOtp = $this->getButtonByText(['conferma'], $browser->driver);
            $this->assertNotNull($confirmOtp);
            $confirmOtp->click();
            //aspetto che si carichi la dashboard
            $browser->waitForLocation('/dashboard');
            $browser->screenshot('firstLoginTest/9.png');
        });
    }
}
