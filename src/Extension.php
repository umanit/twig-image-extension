<?php

declare(strict_types=1);

namespace Umanit\TwigImage;

use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

class Extension extends AbstractExtension
{
    public function getFunctions(): array
    {
        return [
            new TwigFunction(
                'umanit_image_figure',
                [Runtime::class, 'getImageFigure'],
                ['is_safe' => ['html']]
            ),
            new TwigFunction(
                'umanit_image_figure_lazy_load',
                [Runtime::class, 'getImageFigureLazyLoad'],
                ['is_safe' => ['html']]
            ),
            new TwigFunction(
                'umanit_image_picture',
                [Runtime::class, 'getImagePicture'],
                ['is_safe' => ['html']]
            ),
            new TwigFunction(
                'umanit_image_picture_lazy_load',
                [Runtime::class, 'getImagePictureLazyLoad'],
                ['is_safe' => ['html']]
            ),
            new TwigFunction(
                'umanit_image_img',
                [Runtime::class, 'getImgMarkup'],
                ['is_safe' => ['html']]
            ),
            new TwigFunction(
                'umanit_image_srcset',
                [Runtime::class, 'getSrcset'],
                ['is_safe' => ['html']]
            ),
        ];
    }
}
