<?php

namespace App\Migrations;

class CreateTables
{

	public function migrate($schema, $connection)
	{

		$schema->dropIfExists('eps');
		$schema->dropIfExists('clearings');
		$schema->dropIfExists('transactions');


		$schema->create('transactions', function ($table) {
			$table->id();
			$table->string('name');
			$table->timestamps();
		});

		$now = $connection->raw('CURRENT_TIMESTAMP');

		$connection->table('transactions')->insert([
			['name' => 'COMPRA', 'created_at' => $now, 'updated_at' => $now],
			['name' => 'SAQUE', 'created_at' => $now, 'updated_at' => $now],
			['name' => 'CREDITO VOUCHER', 'created_at' => $now, 'updated_at' => $now],
			['name' => 'QUASI-CASH', 'created_at' => $now, 'updated_at' => $now],
			['name' => 'REAPRESENTACAO DE COMPRA', 'created_at' => $now, 'updated_at' => $now],
			['name' => 'LIQUIDACOES VISA', 'created_at' => $now, 'updated_at' => $now],
			['name' => 'LIQUIDACAO DE SAQUE', 'created_at' => $now, 'updated_at' => $now],
			['name' => 'CHARGEBACK DE COMPRA', 'created_at' => $now, 'updated_at' => $now],
			['name' => 'CHARGEBACK DE QUASI-CASH', 'created_at' => $now, 'updated_at' => $now],
			['name' => 'REVERSO DE COMPRA', 'created_at' => $now, 'updated_at' => $now],
			['name' => 'REVERSO DE CHARGEBACK', 'created_at' => $now, 'updated_at' => $now],
			['name' => 'REVERSO DE REAPRESENTACAO', 'created_at' => $now, 'updated_at' => $now],
			['name' => 'COMISSAO DE ATM DECLINE', 'created_at' => $now, 'updated_at' => $now],
			['name' => 'COMISSAO CHARGEBACK DE COMPRA', 'created_at' => $now, 'updated_at' => $now],
			['name' => 'COMISSAO DE QUASI-CASH', 'created_at' => $now, 'updated_at' => $now],
			['name' => 'COMISSAO DE REAPRESENTACAO DE COMPRA', 'created_at' => $now, 'updated_at' => $now],
			['name' => 'COMISSAO DE SAQUE', 'created_at' => $now, 'updated_at' => $now],
			['name' => 'COMISSAO DE CHARGEBACK DE QUASI-CASH', 'created_at' => $now, 'updated_at' => $now],
			['name' => 'COMISSAO DE COMPRA', 'created_at' => $now, 'updated_at' => $now],
			['name' => 'COMISSAO DE CREDITO VOUCHER', 'created_at' => $now, 'updated_at' => $now],
			['name' => 'COMISSAO DE REVERSO DE REAPRESENTACAO', 'created_at' => $now, 'updated_at' => $now],
			['name' => 'COMISSAO DE REVERSO DE CHARGEBACK', 'created_at' => $now, 'updated_at' => $now],
			['name' => 'COMISSAO DE REVERSO DE COMPRA', 'created_at' => $now, 'updated_at' => $now],

			['name' => 'UNKNOWN', 'created_at' => $now, 'updated_at' => $now],
			['name' => 'UNKNOWN-99', 'created_at' => $now, 'updated_at' => $now],
		]);

		$schema->create('clearings', function ($table) {
			$table->id();
			$table->unsignedBigInteger('transaction_id')->nullable();
			$table->foreign('transaction_id')->references('id')->on('transactions')->cascadeOnDelete();
			$table->string('source');
			$table->date('source_date');
			$table->integer('dest_currency');
			$table->integer('arn');
			$table->unsignedBigInteger('slice_code');
			$table->integer('cardbrandid');
			$table->integer('externalid');
			$table->date('local_date');
			$table->date('authorization_date')->nullable();
			$table->float('purchase_value');
			$table->smallInteger('clearing_debit');
			$table->smallInteger('installment_nbr');
			$table->smallInteger('clearing_installment');
			$table->float('installment_value_1');
			$table->float('installment_value_n');
			$table->float('clearing_value');
			$table->float('issuer_exchange_rate');
			$table->float('clearing_commission');
			$table->char('clearing_interchange_fee_sign', 1);
			$table->string('qualifier');
			$table->string('bin_card');
			$table->smallInteger('acquirer_id');
			$table->integer('mcc');
			$table->float('dest_value');
			$table->float('boarding_fee');
			$table->smallInteger('status');
			$table->string('operation_type');
			$table->float('cdt_amount');
			$table->char('product_code', 1);
			$table->string('operation_code');
			$table->string('reason_code');
			$table->string('pan');
			$table->smallInteger('late_presentation');
			$table->string('entry_mode');
			$table->string('pos_entry_mode');
			$table->integer('clearing_files_row_id');
			$table->integer('clearing_currency');
			$table->integer('clearing_boarding_fee');
			$table->date('clearing_settlement_date');
			$table->smallInteger('clearing_presentation');
			$table->smallInteger('clearing_action_code');
			$table->smallInteger('clearing_total_partial_transaction');
			$table->smallInteger('clearing_flag_partial_settlement');
			$table->smallInteger('clearing_cancel');
			$table->smallInteger('clearing_confirm');
			$table->smallInteger('clearing_add');
			$table->smallInteger('clearing_credit');
		});

		$schema->create('eps', function ($table) {
			$table->id();
			$table->unsignedBigInteger('transaction_id')->nullable();
			$table->foreign('transaction_id')->references('id')->on('transactions')->cascadeOnDelete();
			$table->float('value')->nullable();
			$table->char('type_value', 2)->nullable();
			$table->char('currency', 3)->nullable();
			$table->integer('amount')->default(0);
			$table->date('date')->useCurrent();
		});

	}

}
