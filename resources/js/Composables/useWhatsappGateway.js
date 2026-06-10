// WhatsApp Gateway: quando ativado (VITE_WHATSAPP_GATEWAY), os botões de compra
// da loja viram links de WhatsApp ("Olá, tenho interesse em {produto}") em vez de
// adicionar ao carrinho / checkout. Útil pra operar como captação de leads, sem
// pagamento online. Como é flag VITE_, exige rebuild (npm run build) ao alterar.

import { WHATSAPP } from '../data/site.js';

const flag = String(import.meta.env.VITE_WHATSAPP_GATEWAY ?? 'false').toLowerCase();

export const whatsappGateway = ['true', '1', 'on', 'yes', 'sim'].includes(flag);

/**
 * Monta o link do WhatsApp com a mensagem de interesse no produto.
 * @param {string} productName
 * @returns {string}
 */
export function whatsappProductLink(productName) {
    const text = `Olá, tenho interesse em ${(productName ?? '').trim()}`.trim();

    return `https://wa.me/${WHATSAPP.phone}?text=${encodeURIComponent(text)}`;
}
