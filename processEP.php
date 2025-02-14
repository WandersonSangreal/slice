<?php

# header('Content-Type: text/plain; charset=utf-8');

function readEPFile($file)
{

	if (!file_exists($file)) {

		return throw new Exception("file not found");

	}

	return file_get_contents($file);

}

function filterFile($pages, $currency, $negativeFilters)
{

	$filteredCurrency = array_filter($pages, fn($e) => str_contains($e, $currency));

	# array_map(fn($w) => $filteredCurrency = array_filter($filteredCurrency, fn($e) => !str_contains($e, $w)), $negativeFilters);

	foreach ($negativeFilters as $word) {

		$filteredCurrency = array_filter($filteredCurrency, fn($e) => !str_contains($e, $word));

	}

	return $filteredCurrency;

}

function matchValues($contents, $patterns)
{

	return array_map(function ($classification, $pattern) use ($contents) {

		list($report, $keyword, $limiter, $position) = array_pad(explode('|', $pattern), 4, null);

		$pattern = '/' . ($report ? (preg_quote($report, '/') . '.*?') : '') .
			preg_quote($keyword, '/') . '.*?\s(\S+' . preg_quote($limiter) . ')/';

		if ($position === '_SECOND_') {

			$pattern = '/' . ($report ? (preg_quote($report, '/') . '.*?') : '') .
				preg_quote($keyword, '/') . '.*?[\d,]+\.\d+DB\s+(\S+' . preg_quote($limiter) . ')/';

		}

		if (preg_match($pattern, $contents, $matches)) {

			return [$classification => trim($matches[1])];

		}

		return [$classification => null];

	}, $patterns, array_keys($patterns));

}


$file = "files/ep747/EP747_20240705.txt";

try {

	$contents = readEPFile($file);

	$pages = explode("\f", $contents);

	$filterWords = [
		'010203 DESAFIO',
		'CLEARING/REIMBURSEMENT FEES REPORT',
		'PENDING SETTLEMENT REPORT',
		'PENDING FEE SETTLEMENT REPORT',
		'9000113915 FUND TRANSFER'
	];

	$patterns = [
		'|PURCHASE ORIGINAL SALE RECEIVED FROM VISA|DB' => 'COMPRA',
		'|MERCHANDISE CREDIT ORIGINAL RECEIVED FROM VISA|CR' => 'CREDITO VOUCHER',
		'|ATM CASH ORIGINAL WITHDRAWAL RECEIVED FROM VISA|DB' => 'SAQUE',
		'|QUASI-CASH ORIGINAL SALE RECEIVED FROM VISA|DB' => 'QUASI-CASH',
		'|REVERSAL PURCHASE REVERSAL|CR' => 'REVERSO DE COMPRA',

		'|PURCHASE DISPUTE FIN|CR' => 'CHARGEBACK DE COMPRA',
		'RECONCILIATION REPORT|DISPUTE RESP FIN|DB' => 'REAPRESENTACAO DE COMPRA',
		'|NO DEFERMENT PURCHASE REVERSAL|DB' => 'REVERSO DE CHARGEBACK',
		'|ATM DECLINE NNSS DEBITS|DB' => 'COMISSAO DE ATM DECLINE',
		'|PURCHASE DISPUTE FIN|DB' => 'COMISSÃO CHARGEBACK DE COMPRA',


		'27-DAY DEFER|QUASI-CASH ORIGINAL SALE|CR' => 'COMISSAO DE QUASI-CASH',
		'NO DEFERMENT PURCHASE|DISPUTE RESP FIN|CR' => 'COMISSAO DE REAPRESENTACAO DE COMPRA',
		'|NO DEFERMENT PURCHASE REVERSAL|CR' => 'COMISSAO DE REVERSO DE CHARGEBACK',
		'|REVERSAL PURCHASE REVERSAL|DB' => 'COMISSAO DE REVERSO DE COMPRA',
		'|ATM/NATL SETTLED DEBITS|DB|_SECOND_' => 'COMISSAO DE SAQUE',
		'BRAZIL CIP NNSS SERVICE|FUNDS TRANSFER AMOUNT|DB' => 'LIQUIDACOES VISA',
		'BRAZIL CASH DISB NATL NET SERVICE|FUNDS TRANSFER AMOUNT|DB' => 'LIQUIDACAO DE SAQUE',
		'|QUASI-CASH DISPUTE FIN|CR' => 'CHARGEBACK DE QUASI-CASH',
		'|QUASI-CASH DISPUTE FIN|DB' => 'COMISSAO DE CHARGEBACK DE QUASI-CASH',
	];
	$usdPatterns = [
		'|PURCHASE ORIGINAL SALE RECEIVED FROM VISA|DB' => 'COMPRA',
		'|MERCHANDISE CREDIT ORIGINAL RECEIVED FROM VISA|CR' => 'CREDITO VOUCHER',
		'|ATM CASH ORIGINAL WITHDRAWAL RECEIVED FROM VISA|DB' => 'SAQUE',
		# '|QUASI-CASH ORIGINAL SALE RECEIVED FROM VISA|DB' => 'QUASI-CASH',
		# '|REVERSAL PURCHASE REVERSAL|CR' => 'REVERSO DE COMPRA',

		'VISA INTERNATIONAL|DISP FIN DEBITS|CR' => 'CHARGEBACK DE COMPRA',
		'VISA INTERNATIONAL|DISP FIN RVRSL DEBITS|DB' => 'REVERSO DE CHARGEBACK',
		'VISA INTERNATIONAL|DISP RESP RVRSL DEBITS|CR' => 'REVERSO DE REAPRESENTACAO',
		'VISA INTERNATIONAL|DISP FIN DEBITS|DB' => 'COMISSÃO CHARGEBACK DE COMPRA',
		'REIMBURSEMENT FEES REPORT|TOTAL ORIGINAL SALE| ' => 'COMISSAO DE COMPRA',
		'REIMBURSEMENT FEES REPORT|NET MERCHANDISE CREDIT| ' => 'COMISSAO DE CREDITO VOUCHER',
		'VISA INTERNATIONAL|DISP FIN RVRSL DEBITS|CR' => 'COMISSAO DE REVERSO DE CHARGEBACK',
		'VISA INTERNATIONAL|DISP RESP RVRSL DEBITS|DB' => 'COMISSAO DE REVERSO DE REAPRESENTACAO',
		'ATM CASH ORIGINAL WITHDRAWAL VISA L.A.C.|TOTAL VISA L.A.C.| ' => 'COMISSAO DE SAQUE',
		'SRE SETTLEMENT RECAP REPORT|FINAL SETTLEMENT NET AMOUNT|DB' => 'COMISSAO DE SAQUE',
	];

	$filtered = preg_replace('!\s+!', ' ', implode("\n", filterFile($pages, 'BRL', $filterWords)));

	$brlValues = matchValues($filtered, $patterns);

	$usdFiltered = preg_replace('!\s+!', ' ', implode("\n", filterFile($pages, 'USD', $filterWords)));

	$usdValues = matchValues($usdFiltered, $usdPatterns);

	var_dump($brlValues);
	var_dump($usdValues);

	echo "\n\n";

} catch (Exception $exception) {

	echo $exception->getMessage();

}

