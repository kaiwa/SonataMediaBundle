<?php

/*
 * This file is part of the Sonata project.
 *
 * (c) Thomas Rabaix <thomas.rabaix@sonata-project.org>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Bundle\MediaBundle\Provider;

use Bundle\MediaBundle\Entity\BaseMedia as Media;

class Youtube extends BaseProvider {

    public function getReferenceImage(Media $media) {

        return $media->getMetadataValue('thumbnail_url');
    }


    public function getAbsolutePath(Media $media) {

        return sprintf('http://www.youtube.com/v/%s', $media->getProviderReference());
    }

    /**
     *
     * @see f6MediaProvider::preSave
     */
    public function prePersist(Media $media) {

        if (!$media->getBinaryContent()) {

            return;
        }

        $url = sprintf('http://www.youtube.com/oembed?url=http://www.youtube.com/watch?v=%s&format=json', $media->getBinaryContent());
        $metadata = @file_get_contents($url);

        if (!$metadata) {
            throw new \RuntimeException('Unable to retrieve youtube video information for :' . $url);
        }

        $metadata = json_decode($metadata, true);

        if (!$metadata) {
            throw new \RuntimeException('Unable to decode youtube video information for :' . $url);
        }

        $media->setProviderName($this->name);
        $media->setProviderReference($media->getBinaryContent());
        $media->setProviderMetadata($metadata);
        $media->setName($metadata['title']);
        $media->setAuthorName($metadata['author_name']);
        $media->setHeight($metadata['height']);
        $media->setWidth($metadata['width']);
        $media->setContentType('video/x-flv');
        
        return $media;
    }

    public function postRemove(Media $media)
    {
        $files = array(
            $this->getReferenceImage($media),
        );

        foreach($this->formats as $format => $definition) {
            $files[] = $this->generatePrivateUrl($media, $format);
        }


        foreach($files as $file) {
            if(file_exists($file)) {
                unlink($file);
            }
        }
    }

    public function postUpdate(Media $media)
    {

    }

    public function postPersist(Media $media)
    {
        if (!$media->getBinaryContent()) {

            return;
        }

        $this->generateThumbnails($media);
    }

    public function preUpdate(Media $media)
    {

    }

    public function preRemove(Media $media)
    {

    }

}