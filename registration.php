<?php

// Avoid error when running PHP Unit outside of Magento framework
if (class_exists('Magento\Framework\Component\ComponentRegistrar')) {
    \Magento\Framework\Component\ComponentRegistrar::register(
        \Magento\Framework\Component\ComponentRegistrar::MODULE,
        'TransPerfect_GlobalLink',
        __DIR__
    );
}