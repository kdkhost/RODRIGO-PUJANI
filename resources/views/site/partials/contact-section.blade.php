<section id="{{ $embedded ? 'contato' : 'fale-conosco' }}" class="py-24 lg:py-36 relative overflow-hidden" style="background:#0B0C10;">
    <div class="absolute inset-0 grid-pattern opacity-40"></div>
    <div class="absolute left-0 top-0 bottom-0 w-1/2" style="background:radial-gradient(ellipse 70% 70% at 0% 50%, rgba(196,154,60,0.06), transparent);"></div>
    <div class="max-w-7xl mx-auto px-6 lg:px-16 relative">
        <div class="grid lg:grid-cols-2 gap-16 lg:gap-24">
            <div>
                <div class="section-label aos mb-6">— Fale Conosco</div>
                <h2 class="font-display leading-tight mb-6 aos delay-100" style="font-size:clamp(2.2rem,5vw,4rem);font-weight:300;">Inicie sua<br><span class="text-gold-gradient font-semibold">jornada jurídica</span></h2>
                <p class="text-cream/50 leading-relaxed mb-10 aos delay-200 max-w-md">
                    Agende sua primeira consulta sem compromisso. Nossa equipe analisará seu caso e apresentará o melhor caminho jurídico para você.
                </p>

                <div class="space-y-6 aos delay-300">
                    <div class="flex gap-4 items-start">
                        <div class="w-10 h-10 border border-gold/25 flex items-center justify-center flex-shrink-0">
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#C49A3C" stroke-width="1.5"><path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"/><circle cx="12" cy="10" r="3"/></svg>
                        </div>
                        <div>
                            <div class="text-xs text-gold/50 tracking-widest uppercase mb-1">Localização</div>
                            <div class="text-cream/70 text-sm">{{ setting('site.company_address', 'Av. Paulista, 1842 · Conj. 2101 · Bela Vista · São Paulo/SP') }}</div>
                        </div>
                    </div>
                    <div class="flex gap-4 items-start">
                        <div class="w-10 h-10 border border-gold/25 flex items-center justify-center flex-shrink-0">
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#C49A3C" stroke-width="1.5"><path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07A19.5 19.5 0 0 1 4.69 13a19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 3.6 2.18h3a2 2 0 0 1 2 1.72c.127.96.361 1.903.7 2.81a2 2 0 0 1-.45 2.11L7.91 9.91a16 16 0 0 0 6 6l.91-.91a2 2 0 0 1 2.11-.45c.907.339 1.85.573 2.81.7A2 2 0 0 1 22 16.92z"/></svg>
                        </div>
                        <div>
                            <div class="text-xs text-gold/50 tracking-widest uppercase mb-1">Telefone e WhatsApp</div>
                            <div class="text-cream/70 text-sm">{{ setting('site.company_phone', '(11) 3456-7890') }}<br>{{ setting('site.company_whatsapp', '(11) 99876-5432') }}</div>
                        </div>
                    </div>
                    <div class="flex gap-4 items-start">
                        <div class="w-10 h-10 border border-gold/25 flex items-center justify-center flex-shrink-0">
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#C49A3C" stroke-width="1.5"><path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"/><polyline points="22,6 12,13 2,6"/></svg>
                        </div>
                        <div>
                            <div class="text-xs text-gold/50 tracking-widest uppercase mb-1">E-mail</div>
                            <div class="text-cream/70 text-sm">{{ setting('site.company_email', 'contato@pujani.adv.br') }}<br>{{ setting('site.company_secondary_email', 'consultoria@pujani.adv.br') }}</div>
                        </div>
                    </div>
                    <div class="flex gap-4 items-start">
                        <div class="w-10 h-10 border border-gold/25 flex items-center justify-center flex-shrink-0">
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#C49A3C" stroke-width="1.5"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
                        </div>
                        <div>
                            <div class="text-xs text-gold/50 tracking-widest uppercase mb-1">Horário de Atendimento</div>
                            <div class="text-cream/70 text-sm">{{ setting('site.business_hours', 'Seg a Sex: 08h às 18h') }}<br>Urgências: {{ setting('site.company_whatsapp', '(11) 99876-5432') }}</div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="card-glass p-8 lg:p-10 aos-right delay-100">
                <div class="font-display text-2xl font-light text-cream/90 mb-8">Solicitar Consulta Gratuita</div>
                <form id="contact-form" class="space-y-5" data-site-contact-form action="{{ route('site.contact.submit') }}" method="POST">
                    @csrf
                    <input type="hidden" name="source_page" value="{{ $page->slug }}">
                    <div class="grid md:grid-cols-2 gap-4">
                        <input type="text" name="name" class="form-input w-full px-4 py-3.5 text-sm" placeholder="Seu nome completo" required>
                        <input type="email" name="email" class="form-input w-full px-4 py-3.5 text-sm" placeholder="Seu e-mail" required>
                    </div>
                    <div class="grid md:grid-cols-2 gap-4">
                        <input type="tel" name="phone" class="form-input w-full px-4 py-3.5 text-sm" placeholder="(00) 00000-0000" id="tel-input">
                        <input type="text" name="area_interest" class="form-input w-full px-4 py-3.5 text-sm" placeholder="Área de interesse">
                    </div>
                    <input type="text" name="subject" class="form-input w-full px-4 py-3.5 text-sm" placeholder="Assunto">
                    <textarea name="message" class="form-input w-full px-4 py-3.5 text-sm resize-none" rows="4" placeholder="Descreva brevemente sua situação jurídica..." required></textarea>
                    <div class="flex items-start gap-3">
                        <input type="checkbox" id="lgpd" name="consent" class="mt-1" value="1" required>
                        <label for="lgpd" class="text-xs text-cream/40 leading-relaxed cursor-pointer">Concordo com a <a href="{{ route('site.show', 'politica-de-privacidade') }}" class="text-gold/70 hover:text-gold transition-colors underline">Política de Privacidade</a> e o tratamento dos meus dados para fins de contato.</label>
                    </div>
                    <button type="submit" class="btn-primary w-full py-4 text-center"><span>Enviar Solicitação</span></button>
                </form>
                <div id="form-success" class="hidden text-center py-8">
                    <div class="w-14 h-14 border border-gold/40 flex items-center justify-center mx-auto mb-4">
                        <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="#C49A3C" stroke-width="1.5"><polyline points="20 6 9 17 4 12"/></svg>
                    </div>
                    <div class="font-display text-xl text-cream/90 mb-2">Solicitação enviada</div>
                    <div class="text-sm text-cream/50">Nosso time entrará em contato em até 24 horas úteis.</div>
                </div>
            </div>
        </div>
    </div>
</section>
