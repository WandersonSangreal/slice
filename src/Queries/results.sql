-- Soma total de COMPRAS em BRL; (EP747)
SELECT SUM("value") FROM "public"."eps" WHERE "transaction_id" = 1 AND "currency" = 'BRL'; -- 23.226.148,09 (XLSX)
SELECT SUM("value") FROM "public"."eps" WHERE "transaction_id" = 1 AND "currency" = 'USD'; --      9.513,64 (XLSX)

-- Soma total de COMPRAS em BRL; (CLEARINGS)
SELECT SUM("clearing_value") FROM "public"."clearings" WHERE "transaction_id" = 1 AND "clearing_currency" = 986; -- BRL 23.438.117,81 (NOT MATCH, !211.969,72)
SELECT SUM("clearing_value") FROM "public"."clearings" WHERE "transaction_id" = 1 AND "clearing_currency" = 840; -- USD      9.513,64 (OK)

-- Soma total de SAQUES em BRL; (EP747)
SELECT SUM("value") FROM "public"."eps" WHERE "transaction_id" = 2 AND "currency" = 'BRL'; -- 4.917,00 (XLSX)
SELECT SUM("value") FROM "public"."eps" WHERE "transaction_id" = 2 AND "currency" = 'USD'; --    96,10 (XLSX)

-- Soma total de SAQUES em USD; (CLEARINGS)
SELECT SUM("clearing_value") FROM "public"."clearings" WHERE "transaction_id" = 2 AND "clearing_currency" = 986; -- BRL  4.917,00 (OK)
SELECT SUM("clearing_value") FROM "public"."clearings" WHERE "transaction_id" = 2 AND "clearing_currency" = 840; -- USD     96,10 (OK)

-- Soma total de REPASSE LÍQUIDO em BRL; (EP747)

SELECT
	(
			SUM ( CASE WHEN "transaction_id" NOT IN ( SELECT "id" FROM "public"."transactions" WHERE "name" ILIKE'COMISSAO%' ) THEN "value" ELSE 0 END ) -
			SUM ( CASE WHEN "transaction_id" IN ( SELECT "id" FROM "public"."transactions" WHERE "name" ILIKE'COMISSAO%' ) THEN "value" ELSE 0 END )
		) AS "transfer"
FROM
	"public"."eps"
WHERE
		"currency" = 'BRL'; -- 51.390.715,98


SELECT
	(
			SUM ( CASE WHEN "transaction_id" NOT IN ( SELECT "id" FROM "public"."transactions" WHERE "name" ILIKE'COMISSAO%' ) THEN "value" ELSE 0 END ) -
			SUM ( CASE WHEN "transaction_id" IN ( SELECT "id" FROM "public"."transactions" WHERE "name" ILIKE'COMISSAO%' ) THEN "value" ELSE 0 END )
		) AS "transfer"
FROM
	"public"."eps"
WHERE
		"currency" = 'USD'; -- 18.995,12

-- Soma total de REPASSE LÍQUIDO em BRL; (CLEARINGS)

SELECT (SUM("clearing_value") - SUM("clearing_commission")) AS transfer FROM "public"."clearings" WHERE "clearing_currency" = 986; -- BRL 23.332.234,36
SELECT (SUM("clearing_value") - SUM("clearing_commission")) AS transfer FROM "public"."clearings" WHERE "clearing_currency" = 840; -- USD      9.878,27


-- Soma total de REPASSE LÍQUIDO em USD;
