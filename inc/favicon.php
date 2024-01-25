<?php
/**
 * Favicon Data Generator
 */

class FaviconGenerator {
    private $imgix;
    private $iconPath;

    public function __construct() {
        $this->iconPath = get_field('favicon', 'wp_settings');
        add_action('save_options', array($this, 'generateFavicons'));
    }

    private function getImgix() {
        if (!$imgix) {
            $imgix = new Imgix();
        }
        return $imgix;
    }

    public function generateFavicons() {
        if (!current_user_can('manage_options')) {
            return;
        }

        $this->generateManifest();
        $this->generateBrowserConfig();
        $this->generateICO();
    }

    private function generateManifest() {
        $imgix = $this->getImgix();
        
        $manifest = [
            'name' => get_bloginfo('name'),
            'short_name' => get_option('shortname'),
            'start_url' => HEADLESS_FRONTEND_URL,
            'display' => 'standalone',
            'theme_color' => '#F9D236',
            'background_color' => '#000000',
            'icons' => [
                [
                    'src' => $imgix->get_src($this->iconPath, ['fm' => 'png', 'w' => 192, 'h' => 192]),
                    'sizes' => '192x192',
                    'type' => 'image/png',
                ],
                [
                    'src' => $imgix->get_src($this->iconPath, ['fm' => 'png', 'w' => 512, 'h' => 512]),
                    'sizes' => '512x512',
                    'type' => 'image/png',
                ]
            ],
        ];

        $manifestJson = json_encode($manifest, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
        $filePath = get_template_directory() . '/assets/icons/site.webmanifest';

        file_put_contents($filePath, $manifestJson);
    }

    private function generateBrowserConfig() {
        $imgix = $this->getImgix();

        $browserConfig = new SimpleXMLElement('<?xml version="1.0" encoding="UTF-8"?><browserconfig></browserconfig>');
        $msapplication = $browserConfig->addChild('msapplication');
        $tile = $msapplication->addChild('tile');

        $tile->addChild('square70x70logo', '')->addAttribute('src', $imgix->get_src($this->iconPath, ['fm' => 'png', 'w' => 70, 'h' => 70]));
        $tile->addChild('square150x150logo', '')->addAttribute('src', $imgix->get_src($this->iconPath, ['fm' => 'png', 'w' => 150, 'h' => 150]));
        $tile->addChild('square310x310logo', '')->addAttribute('src', $imgix->get_src($this->iconPath, ['fm' => 'png', 'w' => 310, 'h' => 310]));
        $tile->addChild('TileColor', '#000000');

        $browserconfigPath = get_template_directory() . '/assets/icons/browserconfig.xml';
        $browserConfig->asXML($browserconfigPath);
    }

    private function generateICO() {
        try {
            $imgix = $this->getImgix();
            $ico = new PHP_ICO();

            $ico->add_image($imgix->get_src($this->iconPath, ['fm' => 'png', 'w' => 16, 'h' => 16, 'mask' => 'ellipse']));
            $ico->add_image($imgix->get_src($this->iconPath, ['fm' => 'png', 'w' => 32, 'h' => 32, 'mask' => 'ellipse']));
            $ico->add_image($imgix->get_src($this->iconPath, ['fm' => 'png', 'w' => 48, 'h' => 48, 'mask' => 'ellipse']));
            $ico->add_image($imgix->get_src($this->iconPath, ['fm' => 'png', 'w' => 64, 'h' => 64, 'mask' => 'ellipse']));

            $icoFilePath = get_template_directory() . '/assets/icons/favicon.ico';
            $ico->save_ico($icoFilePath);
        } catch (Exception $e) {
            error_log('Error generating ICO: ' . $e->getMessage());
        }
    }
}

new FaviconGenerator();
