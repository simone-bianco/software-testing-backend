<?php

namespace Tests\Browser;

use App\Models\Responsible;
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
            foreach ($inputs as $input) {
                if ($input->getAttribute('type') === 'text') {
                    $input->sendKeys($responsible->email);
                    continue;
                }

                if ($input->getAttribute('type') === 'password') {
                    $input->sendKeys('test');
                }
            }

            $browser->screenshot('firstLoginTest/2.png');
            //click su login
            $browser->element(sprintf('xpath[%s]', self::LOGIN_SUBMIT_XPATH))->click();
            //aspetto che si carichi la pagina
            $browser->waitForLocation('/qr/register');
            $browser->screenshot('firstLoginTest/3.png');
            //prendo la secret key generata
            $secret = $browser->element('div[class=v-card__text]')->getText();
            $google2fa = new Google2FA();
            //ricavo l'otp, come se avessi usato l'applicazione di google
            $currentOtp = $google2fa->getCurrentOtp($secret);
            //scrivo l'otp nell'input
            $browser->type('input[type=text]', $currentOtp);
            $browser->screenshot('firstLoginTest/4.png');
            //click su conferma
            $browser->clickAtXPath(self::TWO_FA_REGISTER_SUBMIT_XPATH);
            $browser->screenshot('firstLoginTest/5.png');
            //aspetto che si carichi
            $browser->waitForLocation('/qr/authenticate')
                ->assertSee('Inserisci Codice OTP');
            $browser->screenshot('firstLoginTest/6.png');
            //ricalcolo l'otp, in quanto il precedente potrebbe essere scaduto
            $currentOtp = $google2fa->getCurrentOtp($secret);
            //inserisco l'otp nell'input text
            $browser->type('input[type=text]', $currentOtp);
            $browser->screenshot('firstLoginTest/7.png');
            //click sul pulsante conferma
            $browser->clickAtXPath(self::TWO_FA_AUTH_SUBMIT_XPATH);
            $browser->screenshot('firstLoginTest/8.png');
            //aspetto che si carichi la dashboard
            $browser->waitForLocation('/dashboard');
            $browser->screenshot('firstLoginTest/9.png');
        });
    }
}
