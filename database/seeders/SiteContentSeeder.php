<?php

namespace Database\Seeders;

use App\Models\Page;
use App\Models\PracticeArea;
use App\Models\TeamMember;
use App\Models\Testimonial;
use Illuminate\Database\Seeder;

class SiteContentSeeder extends Seeder
{
    public function run(): void
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
                'hero_title' => "Defendemos\nseus direitos\ncom estratégia.",
                'hero_subtitle' => 'Há mais de duas décadas, a Pujani Advogados combina conhecimento jurídico profundo, ética inabalável e resultados concretos para pessoas e empresas.',
                'hero_cta_label' => 'Agendar Consulta',
                'hero_cta_url' => '/contato',
                'excerpt' => 'Advocacia estratégica com excelência, ética e resultado.',
                'body' => '<p>Fundado com a missão de democratizar o acesso à advocacia de alto nível, o escritório Pujani Advogados reúne profissionais especializados com atuação consultiva e contenciosa em matérias empresariais, patrimoniais e regulatórias.</p>',
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
                'hero_subtitle' => 'Conheça a história, a visão e a forma de atuação da Pujani Advogados.',
                'hero_cta_label' => 'Solicitar atendimento',
                'hero_cta_url' => '/contato',
                'excerpt' => 'Mais de duas décadas de atuação jurídica sólida, estratégica e orientada por relacionamento.',
                'body' => '<p>Construímos uma prática jurídica baseada em leitura criteriosa do risco, comunicação objetiva e defesa comprometida com resultado. Nosso escritório atende pessoas físicas, famílias empresárias e organizações que exigem resposta técnica, postura firme e visão estratégica.</p>',
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
                'hero_subtitle' => 'Especialidades jurídicas para demandas empresariais, patrimoniais e regulatórias de alta sensibilidade.',
                'hero_cta_label' => 'Falar com especialista',
                'hero_cta_url' => '/contato',
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
                'excerpt' => 'Regras de privacidade, segurança da informação e tratamento de dados do site.',
                'body' => '<p>Tratamos dados pessoais para atendimento jurídico, relacionamento institucional, execução de contratos e cumprimento de obrigações legais. As informações enviadas por formulários, e-mail ou canais digitais são utilizadas de forma compatível com a finalidade informada.</p><p>O titular pode solicitar atualização, acesso, anonimização ou exclusão dos dados, observadas as hipóteses legais de retenção. O contato oficial para assuntos de privacidade é o e-mail institucional do escritório.</p>',
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
                'excerpt' => 'Condições para uso do portal institucional e administrativo.',
                'body' => '<p>O conteúdo do site possui caráter informativo e institucional. Nenhum material publicado constitui parecer jurídico individualizado ou promessa de resultado. A reprodução de conteúdo exige autorização expressa do escritório.</p><p>O usuário compromete-se a utilizar o portal de forma lícita, sem comprometer a segurança, a disponibilidade ou a integridade dos serviços.</p>',
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
                'excerpt' => 'Base legal, finalidade e direitos do titular em conformidade com a LGPD.',
                'body' => '<p>A Pujani Advogados trata dados pessoais com fundamento em bases legais adequadas, incluindo execução de contrato, exercício regular de direitos e legítimo interesse para relacionamento institucional e segurança do ambiente digital.</p><p>O titular pode exercer seus direitos mediante solicitação ao canal oficial de atendimento, sempre com validação de identidade e observância das hipóteses legais aplicáveis.</p>',
                'published_at' => now(),
            ],
        ];

        foreach ($pages as $page) {
            Page::query()->updateOrCreate(['slug' => $page['slug']], $page);
        }

        $areas = [
            ['title' => 'Direito Empresarial', 'slug' => 'direito-empresarial', 'icon' => '⌂', 'highlight' => 'Contratos, M&A, Compliance', 'short_description' => 'Constituição de empresas, contratos societários, fusões e aquisições, governança corporativa e compliance.', 'description' => '<p>Suporte jurídico estratégico para decisões empresariais, reorganizações societárias e estruturas de governança.</p>', 'is_featured' => true, 'is_active' => true, 'sort_order' => 1],
            ['title' => 'Direito Civil', 'slug' => 'direito-civil', 'icon' => '§', 'highlight' => 'Família, Imóveis, Herança', 'short_description' => 'Responsabilidade civil, contratos civis, família e sucessões, imóveis, danos morais e materiais.', 'description' => '<p>Atuação em litígios patrimoniais, relações obrigacionais e proteção de direitos individuais.</p>', 'is_featured' => true, 'is_active' => true, 'sort_order' => 2],
            ['title' => 'Direito Tributário', 'slug' => 'direito-tributario', 'icon' => '$', 'highlight' => 'Planejamento, Fiscal, PGFN', 'short_description' => 'Planejamento tributário, defesas em autuações fiscais, recuperação de créditos e reestruturações.', 'description' => '<p>Estratégia fiscal com foco em redução de contingências e recuperação de oportunidades tributárias.</p>', 'is_featured' => true, 'is_active' => true, 'sort_order' => 3],
            ['title' => 'Direito Trabalhista', 'slug' => 'direito-trabalhista', 'icon' => '⚖', 'highlight' => 'CLT, Rescisão, Assédio', 'short_description' => 'Defesa de empregados e empregadores, reclamações trabalhistas, acordos, mediação e terceirização.', 'description' => '<p>Atuação consultiva e contenciosa para relações de trabalho, sindicatos e prevenção de passivos.</p>', 'is_featured' => true, 'is_active' => true, 'sort_order' => 4],
            ['title' => 'Direito Digital', 'slug' => 'direito-digital', 'icon' => '↯', 'highlight' => 'LGPD, Cibernético, Dados', 'short_description' => 'LGPD, crimes cibernéticos, contratos de tecnologia, privacidade de dados e propriedade intelectual digital.', 'description' => '<p>Assessoria para produtos digitais, incidentes de segurança, políticas de dados e contratos tecnológicos.</p>', 'is_featured' => true, 'is_active' => true, 'sort_order' => 5],
            ['title' => 'Direito Imobiliário', 'slug' => 'direito-imobiliario', 'icon' => '⌂', 'highlight' => 'Locação, Posse, Registro', 'short_description' => 'Compra e venda, locação, regularização de imóveis, incorporações, distratos e ações possessórias.', 'description' => '<p>Consultoria e contencioso em operações imobiliárias, regularizações e disputas de posse.</p>', 'is_featured' => true, 'is_active' => true, 'sort_order' => 6],
        ];

        foreach ($areas as $area) {
            PracticeArea::query()->updateOrCreate(['slug' => $area['slug']], $area);
        }

        $team = [
            ['name' => 'Rafael Pujani', 'slug' => 'rafael-pujani', 'role' => 'Sócio fundador · Civil e Empresarial', 'oab_number' => 'OAB/SP 183.472', 'email' => 'rafael@pujani.adv.br', 'phone' => '(11) 3456-7890', 'whatsapp' => '(11) 99876-5432', 'bio' => '<p>Especialista em Direito Civil e Empresarial, com atuação em grandes corporações e estruturas societárias de alta sensibilidade.</p>', 'specialties' => ['Civil', 'Empresarial'], 'linkedin_url' => 'https://www.linkedin.com/', 'is_partner' => true, 'is_active' => true, 'sort_order' => 1],
            ['name' => 'Ana Carolina Pujani', 'slug' => 'ana-carolina-pujani', 'role' => 'Sócia · Família e Sucessões', 'oab_number' => 'OAB/SP 201.887', 'email' => 'ana@pujani.adv.br', 'phone' => '(11) 3456-7891', 'whatsapp' => '(11) 99876-5433', 'bio' => '<p>Referência em Direito de Família e Sucessões, com abordagem sensível, estratégica e orientada à preservação patrimonial.</p>', 'specialties' => ['Família', 'Sucessões'], 'linkedin_url' => 'https://www.linkedin.com/', 'is_partner' => true, 'is_active' => true, 'sort_order' => 2],
            ['name' => 'Marcos Silveira', 'slug' => 'marcos-silveira', 'role' => 'Sócio · Tributário e Fiscal', 'oab_number' => 'OAB/SP 195.334', 'email' => 'marcos@pujani.adv.br', 'phone' => '(11) 3456-7892', 'whatsapp' => '(11) 99876-5434', 'bio' => '<p>Especialista em planejamento tributário, recuperação de créditos e defesa administrativa de alto valor perante Receita Federal e CARF.</p>', 'specialties' => ['Tributário', 'Fiscal'], 'linkedin_url' => 'https://www.linkedin.com/', 'is_partner' => true, 'is_active' => true, 'sort_order' => 3],
            ['name' => 'Letícia Moura', 'slug' => 'leticia-moura', 'role' => 'Associada sênior · Trabalhista', 'oab_number' => 'OAB/SP 218.661', 'email' => 'leticia@pujani.adv.br', 'phone' => '(11) 3456-7893', 'whatsapp' => '(11) 99876-5435', 'bio' => '<p>Atuação robusta em reclamações trabalhistas, negociações coletivas e questões previdenciárias de alta complexidade.</p>', 'specialties' => ['Trabalhista', 'Previdenciário'], 'linkedin_url' => 'https://www.linkedin.com/', 'is_partner' => false, 'is_active' => true, 'sort_order' => 4],
        ];

        foreach ($team as $member) {
            TeamMember::query()->updateOrCreate(['slug' => $member['slug']], $member);
        }

        $testimonials = [
            ['author_name' => 'Carla Souza', 'author_role' => 'Cliente', 'company' => 'Direito de Família', 'content' => 'O escritório conduziu meu caso com sensibilidade, estratégia e enorme clareza. O resultado foi excelente e o suporte permaneceu em todas as etapas.', 'rating' => 5, 'is_featured' => true, 'is_active' => true, 'sort_order' => 1],
            ['author_name' => 'Fernando Oliveira', 'author_role' => 'CEO', 'company' => 'Oliveira Indústria LTDA', 'content' => 'Recuperamos créditos tributários relevantes com um planejamento cirúrgico. A condução foi técnica, segura e extremamente profissional.', 'rating' => 5, 'is_featured' => true, 'is_active' => true, 'sort_order' => 2],
            ['author_name' => 'Marina Rocha', 'author_role' => 'Empreendedora', 'company' => 'Tech Startup', 'content' => 'A due diligence e a negociação societária foram conduzidas com maestria. O escritório virou parceiro permanente do nosso negócio.', 'rating' => 5, 'is_featured' => true, 'is_active' => true, 'sort_order' => 3],
        ];

        foreach ($testimonials as $testimonial) {
            Testimonial::query()->updateOrCreate(
                ['author_name' => $testimonial['author_name'], 'company' => $testimonial['company']],
                $testimonial
            );
        }
    }
}
