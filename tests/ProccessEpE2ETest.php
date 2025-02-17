<?php

require_once __DIR__ . '/../src/Config/bootstrap.php';

use App\Database\PostgresDatabase;
use App\Migrations\MigrateTables;
use App\Processors\ProcessEp;
use App\Services\InsertService;
use App\Services\TransactionService;
use PHPUnit\Framework\TestCase;

class ProccessEpE2ETest extends TestCase
{

	private static $connection;

	public static function setUpBeforeClass(): void
	{
		$db = new PostgresDatabase($_ENV['DB_HOST_TEST'], $_ENV['DB_NAME_TEST'], $_ENV['DB_USER_TEST'], $_ENV['DB_PASS_TEST']);

		$schema = $db->getSchema();
		self::$connection = $db->getConnection();

		$migrations = new MigrateTables();

		if ($migrations->check($schema)) {

			$migrations->truncate(self::$connection);

		} else {

			$migrations->migrate($schema, self::$connection);

		}
	}

	public function test_should_import_ep_file()
	{

		$transactionService = new TransactionService(self::$connection);
		$transactions = $transactionService->get();

		$pathFolder = realpath(__DIR__ . '/../' . $_ENV['EP_PATH']);

		$files = glob("$pathFolder/*.txt");

		$filterWords = [
			'010203 DESAFIO',
			'CLEARING/REIMBURSEMENT FEES REPORT',
			'PENDING SETTLEMENT REPORT',
			'PENDING FEE SETTLEMENT REPORT',
			'9000113915 FUND TRANSFER'
		];
		$BRLPatterns = [
			'|PURCHASE ORIGINAL SALE RECEIVED FROM VISA|DB' => 'COMPRA',
			'|MERCHANDISE CREDIT ORIGINAL RECEIVED FROM VISA|CR' => 'CREDITO VOUCHER',
			'|ATM CASH ORIGINAL WITHDRAWAL RECEIVED FROM VISA|DB' => 'SAQUE',
			'|QUASI-CASH ORIGINAL SALE RECEIVED FROM VISA|DB' => 'QUASI-CASH',
			'|REVERSAL PURCHASE REVERSAL|CR' => 'REVERSO DE COMPRA',
			'|PURCHASE DISPUTE FIN|CR' => 'CHARGEBACK DE COMPRA',
			'RECONCILIATION REPORT|DISPUTE RESP FIN|DB' => 'REAPRESENTACAO DE COMPRA',
			'|NO DEFERMENT PURCHASE REVERSAL|DB' => 'REVERSO DE CHARGEBACK',
			'|ATM DECLINE NNSS DEBITS|DB' => 'COMISSAO DE ATM DECLINE',
			'|PURCHASE DISPUTE FIN|DB' => 'COMISSAO CHARGEBACK DE COMPRA',
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
		$USDPatterns = [
			'|PURCHASE ORIGINAL SALE RECEIVED FROM VISA|DB' => 'COMPRA',
			'|MERCHANDISE CREDIT ORIGINAL RECEIVED FROM VISA|CR' => 'CREDITO VOUCHER',
			'|ATM CASH ORIGINAL WITHDRAWAL RECEIVED FROM VISA|DB' => 'SAQUE',
			'VISA INTERNATIONAL|DISP FIN DEBITS|CR' => 'CHARGEBACK DE COMPRA',
			'VISA INTERNATIONAL|DISP FIN RVRSL DEBITS|DB' => 'REVERSO DE CHARGEBACK',
			'VISA INTERNATIONAL|DISP RESP RVRSL DEBITS|CR' => 'REVERSO DE REAPRESENTACAO',
			'VISA INTERNATIONAL|DISP FIN DEBITS|DB' => 'COMISSAO CHARGEBACK DE COMPRA',
			'REIMBURSEMENT FEES REPORT|TOTAL ORIGINAL SALE| |_CR_' => 'COMISSAO DE COMPRA',
			'REIMBURSEMENT FEES REPORT|NET MERCHANDISE CREDIT| |_DB_' => 'COMISSAO DE CREDITO VOUCHER',
			'VISA INTERNATIONAL|DISP FIN RVRSL DEBITS|CR' => 'COMISSAO DE REVERSO DE CHARGEBACK',
			'VISA INTERNATIONAL|DISP RESP RVRSL DEBITS|DB' => 'COMISSAO DE REVERSO DE REAPRESENTACAO',
			'ATM CASH ORIGINAL WITHDRAWAL VISA L.A.C.|TOTAL VISA L.A.C.| |_DB_' => 'COMISSAO DE SAQUE',
			'SRE SETTLEMENT RECAP REPORT|FINAL SETTLEMENT NET AMOUNT|DB' => 'LIQUIDACOES VISA',
		];

		$insertService = new InsertService(self::$connection, "eps");

		$processClearing = new ProcessEp(
			$pathFolder, $insertService, $transactions,
			['filters' => $filterWords, 'usdPatterns' => $USDPatterns, 'brlPatterns' => $BRLPatterns]
		);

		$results = $processClearing->processFiles();

		$this->assertEquals($results, array_map(fn($item) => basename($item), $files));

	}

	public function test_should_kept_correct_value_ep_withdrawal_brl()
	{

		$withdrawal = self::$connection->table('eps')->where(['transaction_id' => 1, 'currency' => 'BRL'])->first();

		$this->assertEquals($withdrawal->value, 23226148.09);

	}

}
