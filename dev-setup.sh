#!/bin/bash

TINYMCE_DIR="tiny_mce"
DISTRO_DIR="tinymce-distro/jscripts/$TINYMCE_DIR"

echo "removing old tinymce folder"
rm -r $TINYMCE_DIR
echo "copying distro folder to plugin root"
cp -r $DISTRO_DIR $TINYMCE_DIR
