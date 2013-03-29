<?

require_once('czech_price.inc.php');

$cp = new CzechPrice();

echo "Current BTC price: {$cp->get_price()} CZK\n";
echo "1340.34 CZK is {$cp->czk_to_btc(1340.34)} BTC\n";
echo "2.12345 BTC is {$cp->btc_to_czk(2.12345)} CZK\n";
