<?php

namespace Database\Seeders;

use App\Models\MediaAsset;
use App\Models\Page;
use App\Models\PageSection;
use App\Models\PracticeArea;
use App\Models\TeamMember;
use App\Models\Testimonial;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Cache;

class SiteContentSeeder extends Seeder
{
    private const ASSET_DIR = 'assets/site/premium';

    private bool $force = false;

    public function run(): void
    {
        $this->force = filter_var(env('SEED_PREMIUM_CONTENT_FORCE', false), FILTER_VALIDATE_BOOLEAN);

        $this->seedMediaAssets();
        $this->seedPages();
        $this->seedSections();
        $this->seedPracticeAreas();
        $this->seedTeam();
        $this->seedTestimonials();
        $this->clearCaches();
    }

    private function seedPages(): void
    {
        $pages = [
            [
                'title' => 'Início',
                'slug' => 'home',
                'menu_title' => 'Início',
                'template' => 'home',
                'status' => 'published',
                'is_home' => true,
                'show_in_menu' => true,
                'sort_order' => 1,
                'hero_title' => "Defendemos seus direitos\ncom estratégia e precisão.",
                'hero_subtitle' => 'Atuação jurídica premium para pessoas, famílias e empresas que precisam de clareza, postura e resultado.',
                'hero_cta_label' => 'Agendar Consulta',
                'hero_cta_url' => '/contato',
                'cover_path' => self::ASSET_DIR.'/hero-home.jpg',
                'excerpt' => 'Advocacia estratégica com atendimento próximo, leitura de risco e defesa técnica de alto nível.',
                'body' => '<p>A Pujani Advogados combina experiência contenciosa, consultoria preventiva e comunicação objetiva para entregar uma advocacia sob medida.</p>',
                'published_at' => now(),
            ],
            [
                'title' => 'Sobre',
                'slug' => 'sobre',
                'menu_title' => 'Sobre',
                'template' => 'about',
                'status' => 'published',
                'show_in_menu' => true,
                'sort_order' => 2,
                'hero_title' => 'Sobre o escritório',
                'hero_subtitle' => 'Uma operação jurídica moderna, discreta e orientada por estratégia.',
                'hero_cta_label' => 'Solicitar atendimento',
                'hero_cta_url' => '/contato',
                'cover_path' => self::ASSET_DIR.'/cover-sobre.jpg',
                'excerpt' => 'Mais de duas décadas de atuação sólida, consultiva e contenciosa.',
                'body' => '<p>Construímos uma prática jurídica baseada em leitura criteriosa do risco, comunicação objetiva e defesa comprometida com resultado. O escritório atende pessoas físicas, famílias empresárias e organizações que exigem resposta técnica, postura firme e visão estratégica.</p>',
                'published_at' => now(),
            ],
            [
                'title' => 'Áreas de Atuação',
                'slug' => 'areas-de-atuacao',
                'menu_title' => 'Áreas',
                'template' => 'practice-areas',
                'status' => 'published',
                'show_in_menu' => true,
                'sort_order' => 3,
                'hero_title' => 'Áreas de atuação',
                'hero_subtitle' => 'Especialidades integradas para demandas empresariais, patrimoniais e regulatórias de alta sensibilidade.',
                'hero_cta_label' => 'Falar com especialista',
                'hero_cta_url' => '/contato',
                'cover_path' => self::ASSET_DIR.'/cover-areas.jpg',
                'excerpt' => 'Cobertura jurídica abrangente com especialistas dedicados em cada frente estratégica.',
                'published_at' => now(),
            ],
            [
                'title' => 'Resultados',
                'slug' => 'resultados',
                'menu_title' => 'Resultados',
                'template' => 'results',
                'status' => 'published',
                'show_in_menu' => true,
                'sort_order' => 4,
                'hero_title' => 'Resultados e diferenciais',
                'hero_subtitle' => 'Números consistentes, operação organizada e estratégia jurídica direcionada por performance.',
                'hero_cta_label' => 'Iniciar meu caso',
                'hero_cta_url' => '/contato',
                'cover_path' => self::ASSET_DIR.'/cover-resultados.jpg',
                'excerpt' => 'Atuação focada em previsibilidade, clareza e entrega jurídica com impacto real.',
                'published_at' => now(),
            ],
            [
                'title' => 'Equipe',
                'slug' => 'equipe',
                'menu_title' => 'Equipe',
                'template' => 'team',
                'status' => 'published',
                'show_in_menu' => true,
                'sort_order' => 5,
                'hero_title' => 'Nossa equipe',
                'hero_subtitle' => 'Profissionais com repertório técnico, postura estratégica e compromisso com o cliente.',
                'hero_cta_label' => 'Conhecer especialistas',
                'hero_cta_url' => '/contato',
                'cover_path' => self::ASSET_DIR.'/cover-equipe.jpg',
                'excerpt' => 'Equipe multidisciplinar orientada por excelência técnica e atendimento próximo.',
                'published_at' => now(),
            ],
            [
                'title' => 'Depoimentos',
                'slug' => 'depoimentos',
                'menu_title' => 'Clientes',
                'template' => 'testimonials',
                'status' => 'published',
                'show_in_menu' => true,
                'sort_order' => 6,
                'hero_title' => 'Relacionamentos construídos em confiança',
                'hero_subtitle' => 'Clientes que validam a consistência técnica e a serenidade da nossa atuação.',
                'hero_cta_label' => 'Conversar com a equipe',
                'hero_cta_url' => '/contato',
                'cover_path' => self::ASSET_DIR.'/cover-depoimentos.jpg',
                'excerpt' => 'Relacionamentos construídos sobre confiança, clareza e resultado.',
                'published_at' => now(),
            ],
            [
                'title' => 'Contato',
                'slug' => 'contato',
                'menu_title' => 'Contato',
                'template' => 'contact',
                'status' => 'published',
                'show_in_menu' => true,
                'sort_order' => 7,
                'hero_title' => 'Contato',
                'hero_subtitle' => 'Fale com a equipe Pujani Advogados e receba uma análise inicial do seu caso.',
                'hero_cta_label' => 'Solicitar contato',
                'hero_cta_url' => '/contato',
                'cover_path' => self::ASSET_DIR.'/cover-contato.jpg',
                'excerpt' => 'Canal direto para atendimento jurídico estratégico.',
                'published_at' => now(),
            ],
            [
                'title' => 'Política de Privacidade',
                'slug' => 'politica-de-privacidade',
                'menu_title' => 'Política de Privacidade',
                'template' => 'legal',
                'status' => 'published',
                'show_in_menu' => false,
                'sort_order' => 8,
                'hero_title' => 'Política de Privacidade',
                'hero_subtitle' => 'Transparência sobre coleta, uso, armazenamento e descarte de dados pessoais.',
                'cover_path' => self::ASSET_DIR.'/cover-sobre.jpg',
                'excerpt' => 'Regras de privacidade, segurança da informação e tratamento de dados do site.',
                'body' => '<p>Tratamos dados pessoais para atendimento jurídico, relacionamento institucional, execução de contratos e cumprimento de obrigações legais.</p><p>O titular pode solicitar atualização, acesso, anonimização ou exclusão dos dados, observadas as hipóteses legais de retenção.</p>',
                'published_at' => now(),
            ],
            [
                'title' => 'Termos de Uso',
                'slug' => 'termos-de-uso',
                'menu_title' => 'Termos de Uso',
                'template' => 'legal',
                'status' => 'published',
                'show_in_menu' => false,
                'sort_order' => 9,
                'hero_title' => 'Termos de Uso',
                'hero_subtitle' => 'Condições de navegação e utilização do portal institucional.',
                'cover_path' => self::ASSET_DIR.'/cover-sobre.jpg',
                'excerpt' => 'Condições para uso do portal institucional e administrativo.',
                'body' => '<p>O conteúdo do site possui caráter informativo e institucional. Nenhum material publicado constitui parecer jurídico individualizado ou promessa de resultado.</p><p>O usuário compromete-se a utilizar o portal de forma lícita, sem comprometer a segurança, a disponibilidade ou a integridade dos serviços.</p>',
                'published_at' => now(),
            ],
            [
                'title' => 'Aviso LGPD',
                'slug' => 'aviso-lgpd',
                'menu_title' => 'Aviso LGPD',
                'template' => 'legal',
                'status' => 'published',
                'show_in_menu' => false,
                'sort_order' => 10,
                'hero_title' => 'Aviso LGPD',
                'hero_subtitle' => 'Informações essenciais sobre o tratamento de dados pessoais no ambiente digital da Pujani Advogados.',
                'cover_path' => self::ASSET_DIR.'/cover-sobre.jpg',
                'excerpt' => 'Base legal, finalidade e direitos do titular em conformidade com a LGPD.',
                'body' => '<p>A Pujani Advogados trata dados pessoais com fundamento em bases legais adequadas, incluindo execução de contrato, exercício regular de direitos e legítimo interesse.</p><p>O titular pode exercer seus direitos mediante solicitação ao canal oficial de atendimento, sempre com validação de identidade e observância das hipóteses legais aplicáveis.</p>',
                'published_at' => now(),
            ],
        ];

        foreach ($pages as $page) {
            $this->persist(Page::query()->firstOrNew(['slug' => $page['slug']]), $page);
        }
    }

    private function seedSections(): void
    {
        $home = Page::query()->where('slug', 'home')->first();

        if (! $home) {
            return;
        }

        $sections = [
            [
                'section_key' => 'about',
                'title' => 'O direito é um compromisso com estratégia, clareza e resultado.',
                'subtitle' => 'Quem somos',
                'content' => '<p>Atuamos com discrição, preparo técnico e uma metodologia clara para transformar problemas jurídicos complexos em caminhos possíveis. Cada caso é conduzido por advogado responsável, com comunicação objetiva e acompanhamento próximo.</p>',
                'data' => ['image_path' => self::ASSET_DIR.'/cover-sobre.jpg'],
                'sort_order' => 1,
            ],
            [
                'section_key' => 'metrics',
                'title' => 'Indicadores institucionais',
                'data' => ['items' => [
                    ['value' => '20+', 'counter' => 20, 'suffix' => '+', 'label' => 'Anos de mercado'],
                    ['value' => '2400+', 'counter' => 2400, 'suffix' => '+', 'label' => 'Casos atendidos'],
                    ['value' => '35+', 'counter' => 35, 'suffix' => '+', 'label' => 'Especialistas e parceiros'],
                    ['value' => '98%', 'counter' => 98, 'suffix' => '%', 'label' => 'Satisfação dos clientes'],
                ]],
                'sort_order' => 2,
            ],
            [
                'section_key' => 'timeline',
                'title' => 'Linha do tempo',
                'data' => ['items' => [
                    ['year' => '2003', 'text' => 'Fundação do escritório em São Paulo com foco em Direito Civil e Empresarial.'],
                    ['year' => '2010', 'text' => 'Expansão com novos sócios e consolidação das áreas Tributária e Trabalhista.'],
                    ['year' => '2024', 'text' => 'Reconhecimento nacional entre escritórios de atuação estratégica e atendimento premium.'],
                ]],
                'sort_order' => 3,
            ],
            [
                'section_key' => 'values',
                'title' => 'Valores de atendimento',
                'data' => ['items' => [
                    ['title' => 'Ética e integridade', 'text' => 'Cada caso é conduzido com transparência, clareza e rigor técnico.', 'icon' => 'shield'],
                    ['title' => 'Agilidade e precisão', 'text' => 'Prazos respeitados, estratégia definida e resposta objetiva nos momentos críticos.', 'icon' => 'clock'],
                    ['title' => 'Atendimento humanizado', 'text' => 'Por trás de cada processo existe uma pessoa. O atendimento combina acolhimento e postura.', 'icon' => 'users'],
                ]],
                'sort_order' => 4,
            ],
            [
                'section_key' => 'areas',
                'title' => 'Áreas de <span class="text-gold-gradient font-semibold">Atuação</span>',
                'subtitle' => 'Cobertura jurídica abrangente com especialistas dedicados em cada área do direito brasileiro.',
                'sort_order' => 5,
            ],
            [
                'section_key' => 'results',
                'title' => 'Estratégia jurídica que<br><span class="text-gold-gradient font-semibold">transforma resultados</span>',
                'content' => '<p>Não apenas representamos. Construímos estratégias personalizadas que consideram cada detalhe do caso, o contexto do cliente e os objetivos de negócio.</p>',
                'data' => ['image_path' => self::ASSET_DIR.'/cover-resultados.jpg'],
                'sort_order' => 6,
            ],
            [
                'section_key' => 'differentials',
                'title' => 'Diferenciais',
                'data' => ['items' => [
                    ['title' => 'Atendimento 100% personalizado', 'text' => 'Cada cliente recebe condução próxima de advogado responsável pelo caso.'],
                    ['title' => 'Transparência de honorários', 'text' => 'Propostas claras, contrato detalhado e previsibilidade financeira.'],
                    ['title' => 'Presença digital completa', 'text' => 'Fluxos preparados para acompanhamento remoto e comunicação ágil.'],
                    ['title' => 'Equipe multidisciplinar', 'text' => 'Casos complexos contam com especialistas de áreas complementares.'],
                ]],
                'sort_order' => 7,
            ],
            [
                'section_key' => 'team',
                'title' => 'Nossa <span class="text-gold-gradient font-semibold">Equipe</span>',
                'subtitle' => 'Profissionais com repertório técnico, postura estratégica e compromisso com o cliente.',
                'sort_order' => 8,
            ],
            [
                'section_key' => 'testimonials',
                'title' => 'O que dizem nossos <span class="text-gold-gradient font-semibold">clientes</span>',
                'subtitle' => 'Relacionamentos construídos sobre confiança, clareza e resultado.',
                'sort_order' => 9,
            ],
            [
                'section_key' => 'recognitions',
                'title' => 'Reconhecimentos',
                'data' => ['items' => [
                    ['title' => 'OAB/SP', 'subtitle' => 'Regularidade'],
                    ['title' => 'Análise Advocacia', 'subtitle' => 'Top 50'],
                    ['title' => 'Chambers Brazil', 'subtitle' => 'Referência'],
                    ['title' => 'IBDP', 'subtitle' => 'Membro associado'],
                    ['title' => 'ISO 9001', 'subtitle' => 'Processos'],
                ]],
                'sort_order' => 10,
            ],
            [
                'section_key' => 'contact',
                'title' => 'Inicie sua<br><span class="text-gold-gradient font-semibold">jornada jurídica</span>',
                'content' => '<p>Agende sua primeira consulta sem compromisso. Nossa equipe analisará seu caso e apresentará o melhor caminho jurídico para você.</p>',
                'data' => ['image_path' => self::ASSET_DIR.'/cover-contato.jpg'],
                'sort_order' => 11,
            ],
        ];

        foreach ($sections as $section) {
            $section += [
                'page_id' => $home->id,
                'content' => null,
                'data' => null,
                'style_variant' => 'premium',
                'is_active' => true,
            ];

            $this->persist(PageSection::query()->firstOrNew([
                'page_id' => $home->id,
                'section_key' => $section['section_key'],
            ]), $section);
        }
    }

    private function seedPracticeAreas(): void
    {
        $areas = [
            ['title' => 'Direito Empresarial', 'slug' => 'direito-empresarial', 'icon' => 'EMP', 'highlight' => 'Contratos, M&A, Compliance', 'short_description' => 'Constituição de empresas, contratos societários, fusões e aquisições, governança corporativa e compliance.', 'description' => '<p>Suporte jurídico estratégico para decisões empresariais, reorganizações societárias e estruturas de governança.</p>', 'image_path' => self::ASSET_DIR.'/area-empresarial.jpg', 'is_featured' => true, 'is_active' => true, 'sort_order' => 1],
            ['title' => 'Direito Civil', 'slug' => 'direito-civil', 'icon' => 'CIV', 'highlight' => 'Família, Imóveis, Herança', 'short_description' => 'Responsabilidade civil, contratos civis, família e sucessões, imóveis, danos morais e materiais.', 'description' => '<p>Atuação em litígios patrimoniais, relações obrigacionais e proteção de direitos individuais.</p>', 'image_path' => self::ASSET_DIR.'/area-civil.jpg', 'is_featured' => true, 'is_active' => true, 'sort_order' => 2],
            ['title' => 'Direito Tributário', 'slug' => 'direito-tributario', 'icon' => '$', 'highlight' => 'Planejamento, Fiscal, PGFN', 'short_description' => 'Planejamento tributário, defesas em autuações fiscais, recuperação de créditos e reestruturações.', 'description' => '<p>Estratégia fiscal com foco em redução de contingências e recuperação de oportunidades tributárias.</p>', 'image_path' => self::ASSET_DIR.'/area-tributario.jpg', 'is_featured' => true, 'is_active' => true, 'sort_order' => 3],
            ['title' => 'Direito Trabalhista', 'slug' => 'direito-trabalhista', 'icon' => 'CLT', 'highlight' => 'CLT, Rescisão, Assédio', 'short_description' => 'Defesa de empregados e empregadores, reclamações trabalhistas, acordos, mediação e terceirização.', 'description' => '<p>Atuação consultiva e contenciosa para relações de trabalho, sindicatos e prevenção de passivos.</p>', 'image_path' => self::ASSET_DIR.'/area-trabalhista.jpg', 'is_featured' => true, 'is_active' => true, 'sort_order' => 4],
            ['title' => 'Direito Digital', 'slug' => 'direito-digital', 'icon' => 'LGPD', 'highlight' => 'LGPD, Cibernético, Dados', 'short_description' => 'LGPD, crimes cibernéticos, contratos de tecnologia, privacidade de dados e propriedade intelectual digital.', 'description' => '<p>Assessoria para produtos digitais, incidentes de segurança, políticas de dados e contratos tecnológicos.</p>', 'image_path' => self::ASSET_DIR.'/area-digital.jpg', 'is_featured' => true, 'is_active' => true, 'sort_order' => 5],
            ['title' => 'Direito Imobiliário', 'slug' => 'direito-imobiliario', 'icon' => 'REG', 'highlight' => 'Locação, Posse, Registro', 'short_description' => 'Compra e venda, locação, regularização de imóveis, incorporações, distratos e ações possessórias.', 'description' => '<p>Consultoria e contencioso em operações imobiliárias, regularizações e disputas de posse.</p>', 'image_path' => self::ASSET_DIR.'/area-imobiliario.jpg', 'is_featured' => true, 'is_active' => true, 'sort_order' => 6],
        ];

        foreach ($areas as $area) {
            $this->persist(PracticeArea::query()->firstOrNew(['slug' => $area['slug']]), $area);
        }
    }

    private function seedTeam(): void
    {
        $team = [
            ['name' => 'Rafael Pujani', 'slug' => 'rafael-pujani', 'role' => 'Sócio fundador - Civil e Empresarial', 'oab_number' => 'OAB/SP 183.472', 'email' => 'rafael@pujani.adv.br', 'phone' => '(11) 3456-7890', 'whatsapp' => '(11) 99876-5432', 'bio' => '<p>Especialista em Direito Civil e Empresarial, com atuação em estruturas societárias de alta sensibilidade.</p>', 'specialties' => ['Civil', 'Empresarial'], 'image_path' => self::ASSET_DIR.'/team-rafael-pujani.jpg', 'linkedin_url' => 'https://www.linkedin.com/', 'is_partner' => true, 'is_active' => true, 'sort_order' => 1],
            ['name' => 'Ana Carolina Pujani', 'slug' => 'ana-carolina-pujani', 'role' => 'Sócia - Família e Sucessões', 'oab_number' => 'OAB/SP 201.887', 'email' => 'ana@pujani.adv.br', 'phone' => '(11) 3456-7891', 'whatsapp' => '(11) 99876-5433', 'bio' => '<p>Referência em Direito de Família e Sucessões, com abordagem sensível e orientada à preservação patrimonial.</p>', 'specialties' => ['Família', 'Sucessões'], 'image_path' => self::ASSET_DIR.'/team-ana-carolina-pujani.jpg', 'linkedin_url' => 'https://www.linkedin.com/', 'is_partner' => true, 'is_active' => true, 'sort_order' => 2],
            ['name' => 'Marcos Silveira', 'slug' => 'marcos-silveira', 'role' => 'Sócio - Tributário e Fiscal', 'oab_number' => 'OAB/SP 195.334', 'email' => 'marcos@pujani.adv.br', 'phone' => '(11) 3456-7892', 'whatsapp' => '(11) 99876-5434', 'bio' => '<p>Especialista em planejamento tributário, recuperação de créditos e defesa administrativa de alto valor.</p>', 'specialties' => ['Tributário', 'Fiscal'], 'image_path' => self::ASSET_DIR.'/team-marcos-silveira.jpg', 'linkedin_url' => 'https://www.linkedin.com/', 'is_partner' => true, 'is_active' => true, 'sort_order' => 3],
            ['name' => 'Letícia Moura', 'slug' => 'leticia-moura', 'role' => 'Associada sênior - Trabalhista', 'oab_number' => 'OAB/SP 218.661', 'email' => 'leticia@pujani.adv.br', 'phone' => '(11) 3456-7893', 'whatsapp' => '(11) 99876-5435', 'bio' => '<p>Atuação robusta em reclamações trabalhistas, negociações coletivas e passivos de alta complexidade.</p>', 'specialties' => ['Trabalhista', 'Previdenciário'], 'image_path' => self::ASSET_DIR.'/team-leticia-moura.jpg', 'linkedin_url' => 'https://www.linkedin.com/', 'is_partner' => false, 'is_active' => true, 'sort_order' => 4],
        ];

        foreach ($team as $member) {
            $this->persist(TeamMember::query()->firstOrNew(['slug' => $member['slug']]), $member);
        }
    }

    private function seedTestimonials(): void
    {
        $testimonials = [
            ['author_name' => 'Carla Souza', 'author_role' => 'Cliente', 'company' => 'Direito de Família', 'content' => 'O escritório conduziu meu caso com sensibilidade, estratégia e enorme clareza. O resultado foi excelente e o suporte permaneceu em todas as etapas.', 'rating' => 5, 'image_path' => self::ASSET_DIR.'/avatar-carla-souza.jpg', 'is_featured' => true, 'is_active' => true, 'sort_order' => 1],
            ['author_name' => 'Fernando Oliveira', 'author_role' => 'CEO', 'company' => 'Oliveira Indústria LTDA', 'content' => 'Recuperamos créditos tributários relevantes com um planejamento cirúrgico. A condução foi técnica, segura e extremamente profissional.', 'rating' => 5, 'image_path' => self::ASSET_DIR.'/avatar-fernando-oliveira.jpg', 'is_featured' => true, 'is_active' => true, 'sort_order' => 2],
            ['author_name' => 'Marina Rocha', 'author_role' => 'Empreendedora', 'company' => 'Tech Startup', 'content' => 'A due diligence e a negociação societária foram conduzidas com maestria. O escritório virou parceiro permanente do nosso negócio.', 'rating' => 5, 'image_path' => self::ASSET_DIR.'/avatar-marina-rocha.jpg', 'is_featured' => true, 'is_active' => true, 'sort_order' => 3],
        ];

        foreach ($testimonials as $testimonial) {
            $this->persist(Testimonial::query()->firstOrNew([
                'author_name' => $testimonial['author_name'],
                'company' => $testimonial['company'],
            ]), $testimonial);
        }
    }

    private function seedMediaAssets(): void
    {
        foreach (glob(public_path(self::ASSET_DIR.'/*.jpg')) ?: [] as $file) {
            $path = self::ASSET_DIR.'/'.basename($file);

            MediaAsset::query()->updateOrCreate(
                ['path' => $path],
                [
                    'original_name' => basename($file),
                    'file_name' => basename($file),
                    'disk' => 'public',
                    'directory' => self::ASSET_DIR,
                    'extension' => 'jpg',
                    'mime_type' => 'image/jpeg',
                    'size' => filesize($file) ?: 0,
                    'type' => 'image',
                    'alt_text' => 'Imagem premium institucional Pujani Advogados',
                    'caption' => 'Asset visual premium gerado para o site institucional.',
                    'metadata' => ['source' => 'generated', 'editable' => true],
                    'is_public' => true,
                ],
            );
        }
    }

    private function persist(Model $model, array $data): Model
    {
        if (! $model->exists || $this->force) {
            $model->fill($data);
            $model->save();

            return $model;
        }

        foreach ($data as $key => $value) {
            $current = $model->getAttribute($key);

            if ($current === null || $current === '' || (is_array($current) && $current === [])) {
                $model->setAttribute($key, $value);
            }
        }

        if ($model->isDirty()) {
            $model->save();
        }

        return $model;
    }

    private function clearCaches(): void
    {
        foreach ([
            'site_settings.map.v2',
            'site_pages.menu.v2',
            'site_pages.public.v2',
        ] as $key) {
            Cache::forget($key);
        }
    }
}
