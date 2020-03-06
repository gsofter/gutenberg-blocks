<?php

namespace KWIO\Gutenberg_Blocks;

final class Assets {

    /**
     * Hook registrar.
     */
    public static function register(): void {
        $self = new self();

        add_action('enqueue_block_editor_assets', [$self, 'enqueue_editor_assets']);
        add_action('enqueue_block_assets', [$self, 'enqueue_front_end_assets']);
    }

    public function enqueue_editor_assets(): void {
        $this->enqueue('editor', 'js', ['wp-blocks', 'wp-i18n', 'wp-element', 'wp-editor']);
        $this->enqueue('editor', 'css', ['wp-edit-blocks']);
    }

    public function enqueue_front_end_assets(): void {
        $this->enqueue('blocks', 'css');
        $this->enqueue('blocks', 'js');
    }

    /**
     * Enqueue asset based on file name and file type.
     */
    private function enqueue(string $filename, string $type, array $dependencies = []): void {
        $paths = glob(DIR_PATH . "dist/{$filename}.*.{$type}");
        $path = isset($paths[0]) ? $paths[0] : '';
        $handle = PREFIX . '-' . $filename;
        $src = DIR_URL . ltrim($path, DIR_PATH);
        $media = $filename === 'blocks' ? 'nonblocking' : 'all';
        $block_data = Block_Data::get_instance();
        $block_data->set_context($filename === 'blocks' ? 'frontend' : $filename);

        if (empty($path)) {
            return;
        }

        if ($type === 'js') {
            wp_enqueue_script($handle, $src, $dependencies, null, true);
        }

        if ($type === 'css') {
            wp_enqueue_style($handle, $src, $dependencies, null, $media);
        }

        if ($filename === 'editor') {
            wp_localize_script($handle, 'blockOptions', [
                'defaultBlocks' => Setup::DEFAULT_BLOCKS,
                'data' => $block_data->get_all()
            ]);
        }

        if ($filename === 'blocks' && !is_admin()) {
            if (!empty($block_data->get_all())) {
                wp_localize_script($handle, 'blockData', $block_data->get_all());
            }

            $critical_css_path = DIR_PATH . 'dist/critical.css';
            if (is_readable($critical_css_path)) {
                $critical_css = file_get_contents($critical_css_path);
                $critical_css = str_replace('../../../../', content_url('/'), $critical_css);
                wp_add_inline_style($handle, trim($critical_css));
            }
        }
    }
}