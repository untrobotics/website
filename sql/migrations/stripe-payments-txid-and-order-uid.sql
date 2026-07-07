-- Stripe Checkout support + merch order association.
--
-- Stripe Checkout Session IDs (cs_..., ~66 chars) do not fit the txid columns that
-- were sized for PayPal transaction IDs. Widen them so idempotency + fulfillment
-- inserts succeed. Also associate Printful merch orders with the logged-in buyer.
ALTER TABLE handled_ipns      MODIFY `txid` varchar(255) DEFAULT NULL;
ALTER TABLE dues_payments     MODIFY `txid` varchar(255) DEFAULT NULL;
ALTER TABLE printful_order_tx MODIFY `txid` varchar(255) DEFAULT NULL;
ALTER TABLE printful_order    ADD COLUMN `uid` int(11) DEFAULT NULL;
