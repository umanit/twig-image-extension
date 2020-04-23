<?php

declare(strict_types=1);

namespace Umanit\TwigImage;

use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

/**
 * Class Extension
 */
class Extension extends AbstractExtension
{
    /**
     * @return array
     */
    public function getFunctions(): array
    {
        return [
            new TwigFunction(
                'umanit_image_figure',
                [Runtime::class, 'getUmanitImageFigure'],
                ['is_safe' => ['html']]
            ),
            new TwigFunction(
                'umanit_image_figure_lazy_load',
                [Runtime::class, 'getUmanitImageFigureLazyLoad'],
                ['is_safe' => ['html']]
            ),
            new TwigFunction(
                'umanit_image_picture',
                [Runtime::class, 'getUmanitImagePicture'],
                ['is_safe' => ['html']]
            ),
            new TwigFunction(
                'umanit_image_picture_lazy_load',
                [Runtime::class, 'getUmanitImagePictureLazyLoad'],
                ['is_safe' => ['html']]
            ),
            new TwigFunction(
                'umanit_image_srcset',
                [Runtime::class, 'getUmanitImageSrcset'],
                ['is_safe' => ['html']]
            ),
        ];
    }
}
