<?php
/**
 * @Created by : PhpStorm
 * @Author : Hiệp Nguyễn
 * @At : 28/04/2021, Wednesday
 * @Filename : VietnameseRelatedWordsServiceProvider.php
 **/

namespace Nguyenhiep\VietnameseRelatedWords;


use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;

class VietnameseRelatedWordsServiceProvider extends PackageServiceProvider
{

    public function configurePackage(Package $package): void
    {
        $package->name("nguyenhiep/vietnamese-related-words")
            ->hasConfigFile();
    }
}
