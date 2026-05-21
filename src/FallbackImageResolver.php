<?php

declare(strict_types=1);

namespace Umanit\TwigImage;

use Symfony\Component\Asset\Packages;

final readonly class FallbackImageResolver
{
    private const string RETINA = '2560.png';
    private const string LARGE = '1280.png';
    private const string MEDIUM = '640.png';
    private const string SMALL = '320.png';

    public function __construct(
        private Packages $packages,
    ) {
    }

    public function resolve(string $filter): string
    {
        return $this->packages->getUrl(
            sprintf('images/%s', $this->resolveSizeByFilter($filter)),
            'twig_image_bundle',
        );
    }

    private function resolveSizeByFilter(string $filter): string
    {
        if ('2x' === mb_substr($filter, -2)) {
            return self::RETINA;
        }

        $filter = explode('_', $filter);
        $filter = end($filter);

        return match (true) {
            \in_array($filter, ['xl', 'xxl'], true) => self::LARGE,
            \in_array($filter, ['xxs', 'xs'], true) => self::SMALL,
            default => self::MEDIUM,
        };
    }
}
