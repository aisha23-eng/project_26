const fullDateFormatter = new Intl.DateTimeFormat('ar-SA-u-nu-latn', {
    day: 'numeric',
    month: 'long',
    year: 'numeric',
});

const shortDateFormatter = new Intl.DateTimeFormat('ar-SA-u-nu-latn', {
    day: 'numeric',
    month: 'short',
});

export function formatDate(date: string): string {
    return fullDateFormatter.format(new Date(date));
}

export function formatShortDate(date: string): string {
    return shortDateFormatter.format(new Date(date));
}

export function formatRelativeDate(date: string): string {
    const target = new Date(`${date}T00:00:00`);
    const now = new Date();
    const today = new Date(now.getFullYear(), now.getMonth(), now.getDate());
    const diffDays = Math.round((today.getTime() - target.getTime()) / 86_400_000);

    if (diffDays <= 0) {
        return 'اليوم';
    }
    if (diffDays === 1) {
        return 'أمس';
    }
    if (diffDays < 7) {
        return `منذ ${diffDays} أيام`;
    }
    return formatDate(date);
}
