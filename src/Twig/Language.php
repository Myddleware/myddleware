<?php

namespace App\Twig;

use Symfony\Component\Routing\RequestContextAwareInterface;
use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;

class Language extends AbstractExtension
{
    private RequestContextAwareInterface $request;

    public function __construct(RequestContextAwareInterface $request)
    {
        $this->request = $request;
    }

    public function getFilters(): array
    {
        return [
            new TwigFilter('languages', [$this, 'getAllLanguages']),
        ];
    }

    public function getAllLanguages(array $locales): array
    {
        $defaultLocale = $this->request->getContext()->getParameter('_locale');
        $Locales = [];

        foreach ($locales as $locale) {
            if ($defaultLocale === $locale) {
                $Locales['default'] = $locale;
            } else {
                $Locales['other'][] = $locale;
            }
        }

        return $Locales;
    }
}
