<?php

namespace App\Enums;

use App\Enums\Concerns\HasLabels;

/**
 * Account heads (khat) for khata entries. The AGM report totals income and expense by head.
 */
enum LedgerHead: string
{
    use HasLabels;

    // Income
    case Subscription = 'subscription';
    case AdmissionFee = 'admission_fee';
    case Donation = 'donation';
    case PujaChanda = 'puja_chanda';
    case Sponsorship = 'sponsorship';
    case CateringIncome = 'catering_income';
    case NurserySales = 'nursery_sales';
    case Interest = 'interest';
    case OtherIncome = 'other_income';

    // Expense
    case Electricity = 'electricity';
    case PujaExpenses = 'puja_expenses';
    case Repairs = 'repairs';
    case Stationery = 'stationery';
    case Rent = 'rent';
    case Wages = 'wages';
    case Groceries = 'groceries';
    case PlantsAndPots = 'plants_and_pots';
    case GatewayFee = 'gateway_fee';
    case BankCharges = 'bank_charges';
    case Refunds = 'refunds';
    case OtherExpense = 'other_expense';

    // Cash moved between the cash box and the bank
    case CashTransfer = 'cash_transfer';

    public function englishLabel(): string
    {
        return match ($this) {
            self::Subscription => 'Subscription',
            self::AdmissionFee => 'Admission fee',
            self::Donation => 'Donation',
            self::PujaChanda => 'Puja chanda',
            self::Sponsorship => 'Sponsorship',
            self::CateringIncome => 'Catering income',
            self::NurserySales => 'Nursery sales',
            self::Interest => 'Bank interest',
            self::OtherIncome => 'Other income',
            self::Electricity => 'Electricity',
            self::PujaExpenses => 'Puja expenses',
            self::Repairs => 'Repairs',
            self::Stationery => 'Stationery',
            self::Rent => 'Rent',
            self::Wages => 'Wages',
            self::Groceries => 'Groceries and supplies',
            self::PlantsAndPots => 'Plants and pots',
            self::GatewayFee => 'Payment gateway fee',
            self::BankCharges => 'Bank charges',
            self::Refunds => 'Refunds',
            self::OtherExpense => 'Other expense',
            self::CashTransfer => 'Cash and bank transfer',
        };
    }

    public function type(): LedgerType
    {
        return match ($this) {
            self::Subscription, self::AdmissionFee, self::Donation, self::PujaChanda, self::Sponsorship,
            self::CateringIncome, self::NurserySales, self::Interest, self::OtherIncome => LedgerType::Income,
            self::CashTransfer => LedgerType::Deposit,
            default => LedgerType::Expense,
        };
    }

    /**
     * Heads that may be picked for a hand-written entry of this type.
     *
     * @return list<self>
     */
    public static function forType(LedgerType $type): array
    {
        if ($type->isTransfer()) {
            return [self::CashTransfer];
        }

        return array_values(array_filter(self::cases(), fn (self $head): bool => $head->type() === $type));
    }
}
