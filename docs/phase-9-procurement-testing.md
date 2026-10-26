# Phase 9 — Beginner testing guide

Procurement means buying goods from a supplier. This guide tests office purchases first, then purchases for Projects and Small Businesses.

Use a local testing copy and your own clearly named test records. The upgrade creates no sample purchases, suppliers, users or payments. It keeps your existing accounts and passwords. Do not reset the database to run these checks.

## Before you begin

1. Start the application as usual and log in as your administrator.
2. Open **Users & Roles → Manage Roles**. Check that **Procurement Officer** and **Receiving Officer** exist.
3. Create or assign the separate accounts below. Use a different person/account for each row. Log out before switching accounts, or use separate browser profiles.
4. Under **Members** and **Committees**, create an active **Decision** committee with at least two active members. Assign them to the committee with a joining date on or before today's resolution date. These members must consent to the purchase. Record their consent honestly; the application records an offline resolution, not an online vote.
5. Have a small PDF or JPG/PNG ready for each test quotation, delivery note, invoice and receipt. Clearly mark test evidence as a test. Uploads may be up to 10 MB, subject to your PHP/server upload settings.

| Test account | Role | What this account does |
|---|---|---|
| Purchasing person | Procurement Officer | Creates requests, suppliers and quotations; issues orders; enters invoices |
| Coordinator | Chief Coordinator | First recommendation |
| Secretary | General Secretary | Second review |
| Committee decision person | Decision-Making Committee | Final decision with two member consents; closure/cancellation |
| Receiving person | Receiving Officer | Checks delivered goods and records acceptance |
| Paying person | Treasurer | Records payments, returns and actual refunds |
| Read-only checker | Auditor / Reviewer | Reads reports; cannot pay or approve |

The requester, revision preparer, three decision actors, receiver and payer must remain independent. Linked purchases also enforce the Project/Business payment rules. An administrator cannot bypass these rules. Consenting members must not be the requester, receiver or payer; use additional members for a clear test.

**After every action:** read the green success message or red error, then look at the updated record. Reload before another action if a different account has changed it. A stale-page message means you must reload and check the new figures.

## 1. Prepare an office fund and two suppliers

1. In **Finance → Funds**, create an active unrestricted general fund named `Phase 9 office test`.
2. Record a donation of **PKR 1,000.00** into it using the existing Donations workflow. Confirm the fund has PKR 1,000.00 of ledger cash.
3. Open **Operations → Procurement → Suppliers & procurement rules**.
4. As Procurement Officer, add `Test Supplier A` and `Test Supplier B`. Keep both active.
5. As administrator, check that the quotation count is **2**. Save only if you intend to change the policy. The general Settings preview also links to this working rules screen.

Expected: suppliers are saved, and the fund has actual money. Creating suppliers or changing quotation rules creates no payment.

## 2. Make a PKR 500 office request

1. Log in as Procurement Officer and choose **New purchase request**.
2. Choose **Office**, then click **Load purchase type / budget**.
3. Enter title `Two test chairs`, a purpose, delivery location and a future required date.
4. Choose `Phase 9 office test` and `Test Supplier A`.
5. Add one item: description `Chair`, unit `each`, quantity **2**, unit price **250.00**. Include all taxes, delivery charges and discounts in that price.
6. Click **Save draft**. Write down its `PRC-...` number.
7. Expand **Add supplier quotation**. Enter Supplier A's reference, today's date, future validity date, delivery terms, price **250.00** and evidence. Save.
8. Add a second quotation from Supplier B, for the same quantities and specifications. Use **275.00** per chair. Save.
9. Under **Workflow actions**, choose **Submit**.

Expected: the request moves to the Coordinator stage. It has no ledger debit. The chosen supplier's quotation must match the proposed item prices.

## 3. Approve using three accounts

1. Log out. Log in as Chief Coordinator, open the request, choose **Accept**, enter reasons and click **Record decision**.
2. Repeat as General Secretary using that separate account.
3. Log in as the committee decision person. Select the correct Decision committee, enter a resolution reference and date, upload its evidence, and select at least two consenting members of that committee. Choose Accept and record the decision.
4. Check the request: the approved order total should be **PKR 500.00**.
5. Check the fund: ledger cash should still be **PKR 1,000.00**, with **PKR 500.00 allocated** for this unpaid office order.
6. Log in as Procurement Officer and choose **Issue**. Open **Print purchase order**. The order must show the supplier, approved items, quantities and prices.

Expected: approval and issuing a purchase order never pay the supplier.

## 4. Receive goods and enter the invoice

1. Log in as Receiving Officer.
2. Expand the delivery form. Select Chair, today's receipt date and a delivery-note reference. Enter accepted quantity **2**, rejected quantity **0**, inspection notes and evidence. Save.
3. Log in as Procurement Officer. Expand the invoice form.
4. Enter invoice reference `TEST-INV-001`, today's invoice date and a future due date.
5. For the receipt you just recorded, enter quantity **2**. Leave other receipt rows empty if any are shown. Attach the supplier invoice and save.

Expected: the invoice is PKR 500.00 unpaid. Receipt and invoice entry still create no ledger debit. The application will not invoice rejected or unreceived goods.

## 5. Test the payment arithmetic

1. Log in as Treasurer. Open **Record payment after acceptance**.
2. Choose the accepted invoice line, enter **10.25**, today's date, method, receipt/reference and evidence. Submit once.
3. Confirm **Gross paid = 10.25** and invoice **Unpaid = 489.75**.
4. Try paying **500.00** against the same line. Expect an error. No additional ledger entry should appear.
5. Reload, then pay **489.75** with a different receipt/reference.
6. Confirm **Gross paid = 500.00**, invoice unpaid **0.00**, and fund ledger cash **500.00**.
7. Check Finance Transactions: there must be exactly two posted expense debits for these payments: **10.25** and **489.75**. The office payments also appear under Finance Expenses.
8. Log in as the independent committee decision person and choose **Close**, giving a reason. Archive afterward if desired.

Expected: a closed or archived order accepts no new payments. Double-clicking the same form must not create a second payment. The automated concurrency tests cover genuinely simultaneous submissions; manual clicking alone does not prove concurrency safety.

## 6. Test partial delivery on a separate request

1. Make another two-chair, PKR 500 request and complete approval/order issue.
2. Receive only **1** chair. Invoice only that **1** chair.
3. Try paying **250.01**. Expect rejection. Pay **250.00** instead.
4. Try to invoice the same received chair again. Expect rejection.
5. Receive and invoice the second chair using a new delivery/invoice reference. Pay its **250.00** balance.
6. Try reusing the same supplier invoice reference, including different capitalization or surrounding spaces. Expect rejection, even on another request for the same supplier.
7. Check Procurement Reports: delivery should change from **Partially accepted** to **Accepted in full**.

Expected: no advance payments, over-delivery acceptance or duplicate invoicing. Rejected deliveries do not authorize payment. For an order with several items, record each item's receipt separately.

## 7. Test actual returns and refunds

For an easily checked PKR 100 refund, use a separate office request for **5 packs at PKR 100 each**. Approve, receive all five, invoice and pay PKR 500.

1. As Treasurer, open **Return goods with a supplier credit note**.
2. Select the original receipt and invoice line. Return **1** pack, enter a supplier credit-note reference, return date, reason, expected refund date and evidence. Save.
3. Confirm the supplier owes **PKR 100.00**. The fund balance must **not** increase yet.
4. When the test represents actual money received, open **Record supplier refund received**. Select that return and its original payment, enter **100.00**, date, method, receipt/reference and evidence. Save.
5. Confirm one refund credit to the original fund, gross paid **500.00**, refunds **100.00**, net spent **400.00**, and refund due **0.00**.
6. Try another refund against the same fully refunded return. Expect rejection.
7. Repeat a return before paying a separate invoice. It should reduce the unpaid bill, with no cash credit and no refund receivable for the unpaid portion.
8. A late return/refund can be recorded after closure or archival. It must not reopen payment or authorize replacement goods. Use a separate approved request for replacements.

Expected: a credit note is not cash. Only actual refund recording creates the ledger credit. Small Business post-payment supplier returns are outside this phase; reject unacceptable goods before acceptance/payment there.

## 8. Test a Project purchase

1. Follow the Phase 8 guide to approve a project with a **Purchase** budget item of **PKR 500.00**, payee `Test Supplier A`, an eligible original fund and the required parent approvals. Use a newly approved budget revision for the mandatory-procurement test.
2. From the project page, open its Procurement link. Alternatively, create a request, select **Project**, load the type, choose the project, and load its budget.
3. Choose the exact approved supplier and budget item. The original fund is supplied by the project. Use the same two-chair or five-pack example.
4. Complete procurement quotations, independent approvals, issue, receipt, invoice and payment.
5. Confirm the payment appears in both the project history and procurement history, but there is **only one ledger debit**.
6. Confirm the fund commitment is not doubled. The project already accounts for its approved unpaid budget.
7. Try paying this new goods item directly from the Project payment form. Expect a message requiring Procurement.
8. Try creating a competing order that exceeds the same item's remaining authority. Expect rejection.
9. Try reducing the parent budget below expenditure plus open order obligations or changing a committed supplier. Expect rejection.
10. Test a **PKR 100** return and actual refund as in section 7. Confirm the original project fund and the same budget item receive the restored value exactly once.
11. Project completion permits final procurement bills. Formal project closure/cancellation requires linked requests to be settled and closed/cancelled first. Pending late refund obligations also prevent ending parent funding.

Use one request per supplier. Each selected parent budget item appears at most once in a request; combine equivalent quantities on that line. Different parent budget items may be included in the same order if they have the same approved supplier.

## 9. Test a Small Business purchase

1. Follow the Phase 7 guide to approve a business case with a supplier/asset budget line for `Test Supplier A` and PKR 500.00 of organization funding.
2. Create a linked Small Business purchase request. Complete procurement approval, receipt and invoice.
3. Before signing the business agreements, attempt payment. Expect rejection.
4. Complete the required signed agreements in the Business module, then pay through Procurement with an independent Treasurer who also has Business disbursement permission.
5. Confirm one Business disbursement and one ledger debit. There must be no second generic Expense or office debit.
6. Record the separate beneficiary asset-handover acknowledgement in Business. Supplier receipt did not create this acknowledgement.
7. Close the purchase order when fulfilled and settled, then finalize Business funding using its existing controls. Open procurement obligations must block finalization.

Expected: signed terms, original fund, parent funding status and payer independence all remain enforced. Business cash releases continue through the existing Business workflow.

## 10. Test amendments, correction and permissions

Use separate test requests when testing mutually exclusive paths.

- **Amendment:** choose Amend and provide a reason. Payments pause. Edit the draft, attach new quotations and repeat the full approval chain. Previous approved reservations/history remain visible. Items with receipts keep their supplier, specification, unit price and budget mapping; use another request for different goods.
- **Withdrawal/rejection:** withdraw a proposed amendment, or have an approver reject it. Payments remain paused until **Resume** explicitly restores the previous approved order. A never-issued approved order can be issued after Resume.
- **Cancellation:** an independent authorized closer may cancel unfulfilled obligations with a reason. Accepted goods must first be invoiced or returned; bills and required refunds must be settled. An old pending approval cannot revive a cancelled request.
- **Quotations:** try one quote without a reason, an expired selected quote, the same supplier twice, and a selected price inconsistent with its quote. Expect rejection. Fewer quotes or a higher-priced supplier require both a draft explanation and explicit committee acceptance of the exception.
- **Policy snapshot:** submit with a quotation count of 2, then change the policy to 3. The submitted revision keeps 2; new submissions use 3. Restore your intended policy afterward.
- **Separation:** try self-approval, one person at two approval stages, only one consenting member, requester receiving goods, or receiver making payment. Each must fail.
- **Read-only access:** the Auditor/Reviewer should read reports but have no payment, receipt, policy or approval authority. `procurement.manage` alone must not permit payment or approval.
- **Evidence:** download a file with proper Procurement and parent access. Try as a user without Procurement access; expect access denied. This includes procurement payment evidence reached through Project/Business document links.
- **Voids:** use **Correct a recording error**, choose the record type and ID displayed in history, and give a reason. Void dependent refunds before a return credit note, and dependent credit notes/payments before an invoice/receipt. Records remain in history. Repeating a completed void must not create another ledger movement.
- **Refund reversal:** reversing a refund must fail if it would overdraw the fund or break approved Project limits/reservations. Correct the underlying authorized budget/cash position first; do not bypass the checks.
- **Old data:** previously approved legacy parent budget items retain their historical direct-payment eligibility. New approvals/revisions require Procurement for goods. Pending generic goods expenses must be replaced by a procurement request and cancelled through their existing workflow before any spending; do not re-label goods as services.

## 11. Reports, PDF, spreadsheet and reminders

1. Open **Procurement → Reports** as Treasurer or Auditor/Reviewer.
2. Filter by date, supplier, status, original fund and parent. Check known paid/refunded amounts against Finance Transactions.
3. Read the report's date note: payments/refunds use the selected inclusive date range; approval, invoice, unpaid, refund-due and reservation values are current lifetime totals.
4. Download **XLSX**, open it in Excel or LibreOffice, and check that amounts are numeric and the totals match the screen.
5. Open **Print-friendly report**, press **Ctrl+P**, select **Landscape → Save as PDF**, and inspect every page before sharing. Check headings, table width and totals. Purchase orders can also be printed.
6. Leave a delivery incomplete past its required date, an invoice unpaid past its due date, or a supplier refund outstanding past its expected date. Do this in an isolated testing copy or wait until the dates pass; do not change your real computer clock.
7. Run `php artisan procurement:refresh-reminders` from the project folder. The list should flag the applicable overdue items. On the due date itself they are not overdue.
8. For daily operation, Laravel's scheduler must run. Locally use `php artisan schedule:work` in a terminal you keep open. The procurement refresh is scheduled for **08:15 Asia/Karachi**. Production must invoke Laravel's scheduler every minute using its platform scheduler.

Reminders never pay money or contact a supplier. Receipts support three decimal places; money uses two. Automated tests cover fractional rounding and concurrent submissions.

## Final check

Try ordinary Welfare payment, Qard repayment, Business cash release, Project labor payment, donation and non-goods expense entry using their existing guides. These workflows should still work subject to their original permissions and funds. Record any failure with its request number, exact error and steps; never solve a mismatch by manually inserting another financial transaction.
