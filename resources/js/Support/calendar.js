export const calendarCells = (year, month) => {
    const first = new Date(Date.UTC(year, month, 1));
    const start = new Date(first); start.setUTCDate(1 - (first.getUTCDay() + 6) % 7);
    return Array.from({ length: 42 }, (_, index) => {
        const date = new Date(start); date.setUTCDate(start.getUTCDate() + index);
        return { key: date.toISOString().slice(0, 10), day: date.getUTCDate(), currentMonth: date.getUTCMonth() === month };
    });
};
export const wibNow = () => {
    const parts = Object.fromEntries(new Intl.DateTimeFormat('en-CA', { timeZone: 'Asia/Jakarta', year: 'numeric', month: '2-digit', day: '2-digit', hour: '2-digit', minute: '2-digit', hourCycle: 'h23' }).formatToParts(new Date()).map(part => [part.type, part.value]));
    return `${parts.year}-${parts.month}-${parts.day}T${parts.hour}:${parts.minute}`;
};
