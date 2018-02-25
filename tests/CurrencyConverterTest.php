<?php
declare(strict_types=1);

use PHPUnit\Framework\TestCase;

final class CurrencyConverterTest extends TestCase
{
  public function sample_rates()
  {
    $eur_rates = array('USD' => 1.5, 'GBP' => 0.5);

    return array('EUR' => $eur_rates);
  }


  /*
   * Fetching xml from EUCB and testing XML parse function is not assumed
   * to be part of the exercise, especially as the function comes from EUCB.
   * Therefore mocking get_rates() method instead of webservice call response.
   */
  public function eucb_rates_sample()
  {
    $eucb = $this->getMockBuilder(EucbRates::class)
                 ->setMethods(['get_rates'])
                 ->getMock();
    $eucb->expects($this->any())
         ->method('get_rates')
         ->willReturn($this->sample_rates());

    return $eucb;
  }


  public function test_it_converts(): void
  {
    $conv = new CurrencyConverter($this->eucb_rates_sample());

    $this->assertEquals(50, $conv->convert(100, 'EUR', 'GBP'));
  }


  public function test_it_initializes_default_rates(): void
  {
    $conv = new CurrencyConverter();

    $this->assertInstanceOf(EucbRates::class, $conv->rates_src);
  }


  public function test_it_fails_when_missing_rates(): void
  {
    $conv = new CurrencyConverter($this->eucb_rates_sample());

    $this->expectException(MissingCurrencyRateException::class);
    $conv->convert(100, 'GBP', 'EUR');
  }


  public function test_it_fails_when_missing_single_rate(): void
  {
    $conv = new CurrencyConverter($this->eucb_rates_sample());

    $this->expectException(MissingCurrencyRateException::class);
    $conv->convert(100, 'EUR', 'JPY');
  }


  public function test_it_rounds_up(): void
  {
    $conv = new CurrencyConverter($this->eucb_rates_sample());
    $conv->set_round_up();
    $this->assertEquals(50, $conv->convert(99, 'EUR', 'GBP'));
  }


  public function test_it_rounds_down(): void
  {
    $conv = new CurrencyConverter($this->eucb_rates_sample());
    $conv->set_round_down();
    $this->assertEquals(49, $conv->convert(99, 'EUR', 'GBP'));
  }
}
