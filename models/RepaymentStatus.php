<?php

/**
 * Repayment Status lookup - represents where the client's repayment
 * stands (Not Due, Pending Payment, Partially Paid, Paid, Defaulted,
 * Rolled Over). This is intentionally separate from Loan Status, which
 * tracks the application/disbursement lifecycle instead.
 */
class RepaymentStatus extends Model
{
    protected string $table = 'repayment_statuses';
}
