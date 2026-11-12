export function quantityPrice(regular, campaign, tiers, quantity) {
    const percentage = (tiers || []).reduce((best, tier) =>
        Number(tier.min_quantity) <= quantity ? Math.max(best, Number(tier.percentage)) : best, 0);
    const cents = Math.round(regular * 100);
    const basisPoints = Math.round(percentage * 100);
    const personal = Math.round(cents * (10000 - basisPoints) / 10000) / 100;
    return Math.max(0, Math.min(regular, campaign, personal));
}
