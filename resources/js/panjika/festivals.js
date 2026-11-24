// "Fill this year's festivals" (Console → Panjika): the dates of the usual Bengal pujas and parbon
// for one Bengali year, worked out from the sun and moon (calc.js). The committee checks them against
// the club's panjika before they are saved; each saved one is marked "calculated" until it is checked.
//
// Lunar months are amanta (new moon to new moon) and named after the solar month the new moon falls in
// (so lunar Magh begins at the new moon in solar Magh); an adhik (extra) month is skipped.
// Which day a tithi festival falls on:
//   sunrise      – the day the tithi holds at sunrise (udaya tithi); if it holds at two sunrises, the first;
//                  if it begins and ends between two sunrises, the day it begins
//   sunrise-last – the same, but the second day when it holds at two sunrises (Mahashtami: the Ashtami
//                  joined with Navami)
//   forenoon     – the first day the tithi holds at 11 am (Bijoya Dashami: Bengal immerses on the morning
//                  Dashami holds, so 21 Oct 2026 though the all-India Dussehra was the 20th)
//   midnight     – the night the tithi holds at midnight (Kali Puja, Kojagari, Janmashtami, Shivaratri)
// A date is marked "irregular" when its tithi holds at two sunrises or at none, or the timing rule moves it off
// the sunrise day: that is where panjikas differ.
// Solar ones: first (1st day of a Bengali month) or last (its sankranti, the last day).
import * as A from 'astronomy-engine';
import { addDays, bengaliDate, istMidnight, keyOf, panjikaDay, parseKey, tithiAt } from './calc.js';

const DAY = 86400000;

/** [kind, English, Bengali, rule] — rule: ['tithi', lunar month 0–11, tithi 0–29, timing] or ['solar', month, 'first'|'last'] */
export const FESTIVALS = [
    ['parbon', 'Pohela Boishakh · Bengali New Year', 'পয়লা বৈশাখ · নববর্ষ', ['solar', 0, 'first']],
    ['parbon', 'Akshaya Tritiya', 'অক্ষয় তৃতীয়া', ['tithi', 0, 2, 'sunrise']],
    ['parbon', 'Jamai Shashthi', 'জামাইষষ্ঠী', ['tithi', 1, 5, 'sunrise']],
    ['parbon', 'Rath Yatra', 'রথযাত্রা', ['tithi', 2, 1, 'sunrise']],
    ['puja', 'Janmashtami', 'জন্মাষ্টমী', ['tithi', 3, 22, 'midnight']],
    ['puja', 'Vishwakarma Puja', 'বিশ্বকর্মা পূজা', ['solar', 4, 'last']],
    ['parbon', 'Mahalaya', 'মহালয়া', ['tithi', 4, 29, 'sunrise']],
    ['puja', 'Durga Puja · Shashthi (Bodhon)', 'দুর্গাপূজা · ষষ্ঠী (বোধন)', ['tithi', 5, 5, 'sunrise-last']],
    ['puja', 'Durga Puja · Maha Saptami', 'দুর্গাপূজা · মহাসপ্তমী', ['tithi', 5, 6, 'sunrise']],
    ['puja', 'Durga Puja · Maha Ashtami', 'দুর্গাপূজা · মহাষ্টমী', ['tithi', 5, 7, 'sunrise-last']],
    ['puja', 'Durga Puja · Maha Navami', 'দুর্গাপূজা · মহানবমী', ['tithi', 5, 8, 'sunrise']],
    ['puja', 'Bijoya Dashami', 'বিজয়া দশমী', ['tithi', 5, 9, 'forenoon']],
    ['puja', 'Kojagari Lakshmi Puja', 'কোজাগরী লক্ষ্মীপূজা', ['tithi', 5, 14, 'midnight']],
    ['puja', 'Kali Puja', 'কালীপূজা', ['tithi', 5, 29, 'midnight']],
    ['parbon', 'Bhai Phonta', 'ভাইফোঁটা', ['tithi', 6, 1, 'sunrise']],
    ['puja', 'Jagaddhatri Puja', 'জগদ্ধাত্রী পূজা', ['tithi', 6, 8, 'sunrise']],
    ['puja', 'Rash Purnima', 'রাসপূর্ণিমা', ['tithi', 6, 14, 'sunrise']],
    ['parbon', 'Poush Sankranti · pithe-puli', 'পৌষ সংক্রান্তি · পিঠে-পুলি', ['solar', 8, 'last']],
    ['puja', 'Saraswati Puja', 'সরস্বতী পূজা', ['tithi', 9, 4, 'sunrise']],
    ['puja', 'Shivaratri', 'শিবরাত্রি', ['tithi', 9, 28, 'midnight']],
    ['puja', 'Dol Purnima', 'দোলপূর্ণিমা', ['tithi', 10, 14, 'sunrise']],
    ['puja', 'Annapurna Puja', 'অন্নপূর্ণা পূজা', ['tithi', 11, 7, 'sunrise']],
    ['puja', 'Ram Navami', 'রামনবমী', ['tithi', 11, 8, 'sunrise']],
    ['parbon', 'Choitro Sankranti · Charak', 'চৈত্র সংক্রান্তি · চড়ক', ['solar', 11, 'last']],
];

/** First and last day ('YYYY-MM-DD') of Bengali year `y` (1 Boishakh to the last day of Choitro). */
export function bengaliYearRange(y) {
    // 1 Boishakh falls on 14 or 15 April
    let first = `${y + 593}-04-13`;
    while (!(bengaliDate(first).month === 0 && bengaliDate(first).day === 1)) first = addDays(first, 1);
    let next = `${y + 594}-04-13`;
    while (!(bengaliDate(next).month === 0 && bengaliDate(next).day === 1)) next = addDays(next, 1);
    return { first, last: addDays(next, -1) };
}

/** Lunar months (new moon to new moon) overlapping [from, to]: { month 0–11, start, end, adhik }. */
function lunarMonths(from, to) {
    const { y, m, d } = parseKey(addDays(from, -45));
    let t = A.MakeTime(istMidnight(y, m, d));
    const moons = [];
    const stop = istMidnight(...Object.values(parseKey(addDays(to, 45)))).getTime();
    while (moons.length < 20) {
        const nm = A.SearchMoonPhase(0, t, 40);
        if (!nm || nm.date.getTime() > stop) break;
        moons.push(nm.date);
        t = nm.AddDays(1);
    }
    const months = [];
    for (let i = 0; i + 1 < moons.length; i++) {
        const month = bengaliDate(keyOf(moons[i])).month;
        const nextMonth = bengaliDate(keyOf(moons[i + 1])).month;
        months.push({ month, start: moons[i], end: moons[i + 1], adhik: month === nextMonth });
    }
    return months;
}

/** The tithi at a clock time (IST) on day k. */
const tithiAtClock = (k, hours) => { const { y, m, d } = parseKey(k); return tithiAt(new Date(istMidnight(y, m, d).getTime() + hours * 3600000)).i; };

/**
 * The day a tithi festival falls on within one lunar month, by its timing rule: { date, irregular }.
 * irregular: the tithi holds at two sunrises, or begins and ends between two (panjikas may differ).
 */
function tithiDay(lm, tithi, rule) {
    const firstKey = keyOf(lm.start);
    const lastKey = keyOf(lm.end);
    const inMonth = (when) => when >= lm.start && when <= lm.end;
    // Every day of the lunar month, with its sunrise tithi
    const days = [];
    for (let k = firstKey; k <= lastKey; k = addDays(k, 1)) days.push({ k, p: panjikaDay(k) });
    const atSunrise = days.filter(({ p }) => p.sun.rise && inMonth(p.sun.rise) && p.tithi.i === tithi).map(({ k }) => k);
    const irregular = atSunrise.length !== 1;

    if (rule === 'midnight' || rule === 'forenoon') {
        const hours = rule === 'midnight' ? 23.75 : 11;
        for (const { k } of days) {
            const { y, m, d } = parseKey(k);
            if (!inMonth(new Date(istMidnight(y, m, d).getTime() + hours * 3600000))) continue;
            if (tithiAtClock(k, hours) === tithi) return { date: k, irregular: irregular || (rule === 'forenoon' && atSunrise[0] !== k) };
        }
        return null;
    }
    if (atSunrise.length) return { date: rule === 'sunrise-last' ? atSunrise[atSunrise.length - 1] : atSunrise[0], irregular };
    // Kshaya: the tithi begins and ends between two sunrises; the day it begins
    const begins = days.find(({ p }) => p.nextTithi?.i === tithi);
    return begins ? { date: begins.k, irregular: true } : null;
}

/** First or last day of Bengali month `month` that lies in [from, to]. */
function solarDay(month, which, from, to) {
    for (let k = from; k <= to; k = addDays(k, 1)) {
        const b = bengaliDate(k);
        if (b.month !== month) continue;
        if (which === 'first' && b.day === 1) return k;
        if (which === 'last' && bengaliDate(addDays(k, 1)).month !== month) return k;
    }
    return null;
}

/**
 * The festivals of Bengali year `y`, in date order:
 * [{ date, kind, name, name_bn, tithi (0–29 or null), when, irregular }].
 */
export function festivalsForYear(y) {
    const { first, last } = bengaliYearRange(y);
    const months = lunarMonths(first, last).filter((lm) => !lm.adhik);
    const out = [];
    for (const [kind, name, nameBn, rule] of FESTIVALS) {
        if (rule[0] === 'solar') {
            const date = solarDay(rule[1], rule[2], first, last);
            if (date) out.push({ date, kind, name, name_bn: nameBn, tithi: null, when: 'solar', irregular: false });
            continue;
        }
        const [, month, tithi, when] = rule;
        for (const lm of months.filter((x) => x.month === month)) {
            const day = tithiDay(lm, tithi, when);
            if (day && day.date >= first && day.date <= last) out.push({ date: day.date, kind, name, name_bn: nameBn, tithi, when, irregular: day.irregular });
        }
    }
    return out.sort((a, b) => a.date.localeCompare(b.date));
}

