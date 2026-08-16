export type TransactionType = 'expense' | 'income';

export type TransactionCategory = {
    id: number;
    name: string;
    color: string | null;
    icon: string | null;
};

export type Transaction = {
    id: number;
    description: string;
    vendor: string | null;
    amount: number;
    date: string;
    type: TransactionType | null;
    category: TransactionCategory | null;
};

export type TransactionStats = {
    total_balance: number;
    total_income: number;
    total_expenses: number;
    monthly_income: number;
    monthly_expenses: number;
    net_monthly: number;
    previous_month_income: number;
    previous_month_expenses: number;
    previous_month_net: number;
    currency: string;
};

export type QuickStats = {
    average_daily_expense: number;
    expense_count: number;
    largest_transaction: Transaction | null;
};

export type CategoryItem = {
    id: number;
    name: string;
    type: TransactionType;
    icon: string;
    color: string;
    is_system: boolean;
    expenses_count: number;
};

export type CategoryBreakdownItem = {
    name: string;
    color: string | null;
    amount: number;
    percentage: number;
};

export type MonthlySeriesItem = {
    label: string;
    expenses: number;
    income: number;
};

export type ReportTotals = {
    total_expenses: number;
    total_income: number;
    net: number;
    avg_monthly_expenses: number;
};
