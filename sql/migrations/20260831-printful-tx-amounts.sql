-- URW-232: capture merch (Printful) sale amounts locally so the AR ledger has a
-- complete gross/fee/net for every stream. Historically only the txid + Printful
-- order id were stored; the dollar amounts lived only in Stripe/PayPal. These
-- columns are populated going forward by the printful IPN handler; historical
-- rows stay NULL and are surfaced as "unrecorded" in the ledger for reconciliation.
ALTER TABLE `printful_order_tx`
    ADD COLUMN `amount` decimal(10,2) DEFAULT NULL,
    ADD COLUMN `fee` decimal(10,2) DEFAULT NULL;
