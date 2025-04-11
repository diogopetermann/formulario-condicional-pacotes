<?php
/**
 * Plugin Name: Formulário condicional para pacotes (Contact Form 7)
 * Description: Campo condicional na escolha de pacotes nacionais e internacionais. Funciona apenas nas páginas 231 e 235.
 * Version: 1.4
 * Author: Diogo Petermann
 * Author URI: https://www.diogopetermann.com.br
 */

if (!defined('ABSPATH')) exit;

// Include plugin update checker
require plugin_dir_path(__FILE__) . 'plugin-update-checker/plugin-update-checker.php';
use YahnisElsts\PluginUpdateChecker\v4\PucFactory;
$updateChecker = PucFactory::buildUpdateChecker(
    'https://github.com/diogopetermann/formulario-condicional-pacotes/',
    __FILE__,
    'formulario-condicional-pacotes'
);

// Admin menu
add_action('admin_menu', function () {
    add_options_page(
        'Formulário Condicional',
        'Formulário Condicional',
        'manage_options',
        'formulario-condicional-pacotes',
        'fcp_render_options_page'
    );
});

// Register settings
add_action('admin_init', function () {
    register_setting('fcp_settings_group', 'fcp_page_ids');
    register_setting('fcp_settings_group', 'fcp_conditional_rules');
});

// Admin page content
function fcp_render_options_page() {
    ?>
    <div class="wrap">
        <h1>Formulário Condicional para Pacotes <small>- versão 1.4</small></h1>
        <h2>Como usar</h2>
        <ol>
            <li>Adicione um campo do tipo select em seu formulário com o mesmo nome que você informará abaixo;</li>
            <li>Crie um campo de texto em seu formulário que ficará visível apenas quando a opção configurada abaixo em "Valor que ativa" for selecionada. Ex.: Se a última opção do select em seu formulário for "Outros", digite exatamente Outros no campo "Valor que ativa" abaixo.</li>
            <li>Envolva esse campo de texto em um container com o ID configurado em "ID do container" abaixo. Ex.: Se você escolher como "ID do container" o texto outros-container, coloque seu campo de texto assim: <code>&lt;div id="outros-container" style="display:none;"&gt;[text text-outros]&lt;/div&gt;</code></li>
            <li>O script será executado apenas nas páginas com os IDs configurados.</li>
        </ol>

        <form method="post" action="options.php">
            <?php
            settings_fields('fcp_settings_group');
            do_settings_sections('fcp_settings_group');
            $page_ids = esc_attr(get_option('fcp_page_ids'));
            $rules = get_option('fcp_conditional_rules', []);
            ?>
            <table class="form-table">
                <tr valign="top">
                    <th scope="row">IDs das páginas (separados por vírgula)</th>
                    <td><input type="text" name="fcp_page_ids" value="<?php echo $page_ids; ?>" class="regular-text" /></td>
                </tr>
            </table>

            <h2>Regras condicionais</h2>
            <table id="fcp-conditional-rules" class="widefat striped">
                <thead>
                    <tr>
                        <th>Nome do campo SELECT</th>
                        <th>Valor que ativa</th>
                        <th>ID do container</th>
                        <th>Ações</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!empty($rules)) : ?>
                        <?php foreach ($rules as $index => $rule) : ?>
                            <tr>
                                <td><input type="text" name="fcp_conditional_rules[<?php echo $index; ?>][select_name]" value="<?php echo esc_attr($rule['select_name']); ?>" class="regular-text" /></td>
                                <td><input type="text" name="fcp_conditional_rules[<?php echo $index; ?>][trigger_value]" value="<?php echo esc_attr($rule['trigger_value']); ?>" class="regular-text" /></td>
                                <td><input type="text" name="fcp_conditional_rules[<?php echo $index; ?>][container_id]" value="<?php echo esc_attr($rule['container_id']); ?>" class="regular-text" /></td>
                                <td><button type="button" class="button remove-rule">Remover</button></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
            <p><button type="button" class="button" id="add-rule">Adicionar regra</button></p>

            <?php submit_button(); ?>
        </form>
    </div>
    <script>
        document.getElementById('add-rule').addEventListener('click', function () {
            const tableBody = document.querySelector('#fcp-conditional-rules tbody');
            const index = tableBody.rows.length;
            const row = document.createElement('tr');

            row.innerHTML = `
                <td><input type="text" name="fcp_conditional_rules[${index}][select_name]" class="regular-text" /></td>
                <td><input type="text" name="fcp_conditional_rules[${index}][trigger_value]" class="regular-text" /></td>
                <td><input type="text" name="fcp_conditional_rules[${index}][container_id]" class="regular-text" /></td>
                <td><button type="button" class="button remove-rule">Remover</button></td>
            `;
            tableBody.appendChild(row);
        });

        document.addEventListener('click', function (e) {
            if (e.target && e.target.classList.contains('remove-rule')) {
                e.target.closest('tr').remove();
            }
        });
    </script>
    <style>
        @media (max-width: 768px) {
            #fcp-conditional-rules, #fcp-conditional-rules thead, #fcp-conditional-rules tbody, #fcp-conditional-rules tr, #fcp-conditional-rules td, #fcp-conditional-rules th {
                display: block;
                width: 100%;
            }
            #fcp-conditional-rules thead {
                display: none;
            }
            #fcp-conditional-rules td {
                margin-bottom: 10px;
            }
            #fcp-conditional-rules td::before {
                content: attr(data-label);
                font-weight: bold;
                display: block;
            }
        }
    </style>
    <?php
}

// Conditional JavaScript
add_action('wp_enqueue_scripts', function () {
    if (is_admin()) return;

    $page_ids = explode(',', get_option('fcp_page_ids', ''));
    $current_id = get_queried_object_id();
    $page_ids = array_map('trim', $page_ids);

    if (in_array($current_id, $page_ids)) {
        wp_enqueue_script('fcp-conditional-js', plugin_dir_url(__FILE__) . 'formulario-condicional.js', [], null, true);
        wp_localize_script('fcp-conditional-js', 'fcpRules', get_option('fcp_conditional_rules', []));
    }
});

// Add plugin settings link
add_filter('plugin_action_links_' . plugin_basename(__FILE__), function ($links) {
    $settings_link = '<a href="options-general.php?page=formulario-condicional-pacotes">Configurações</a>';
    array_unshift($links, $settings_link);
    return $links;
});
