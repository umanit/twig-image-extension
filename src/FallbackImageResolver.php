<?php

declare(strict_types=1);

namespace Umanit\TwigImage;

use Symfony\Component\Asset\Packages;

class FallbackImageResolver
{
    private const RETINA = '2560.png';
    private const LARGE = '1280.png';
    private const MEDIUM = '640.png';
    private const SMALL = '320.png';

    private $packages;

    public function __construct(Packages $packages)
    {
        $this->packages = $packages;
    }

    public function resolve(string $filter)
    {
        return $this->packages->getUrl(
            sprintf('images/%s', $this->resolveSizeByFilter($filter)),
            'twig_image_bundle'
        );
    }

    private function resolveSizeByFilter(string $filter): string
    {
        if ('2x' === mb_substr($filter, -2)) {
            return self::RETINA;
        }

        $filter = explode('_', $filter);
        $filter = end($filter);

        switch (true) {
            case in_array($filter, ['xl', 'xxl']):
                return self::LARGE;
            case in_array($filter, ['xxs', 'xs']):
                return self::SMALL;
            default:
                return self::MEDIUM;
        }
    }
}
