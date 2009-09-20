#!/bin/bash

DEV_DIR="dev"
DISTRO_DIR="tinymce-distro/jscripts/tiny_mce"

echo "removing old dev folder"
rm -r $DEV_DIR
echo "creating new dev folder"
mkdir $DEV_DIR
echo "copying distro folder to dev root"
cp -r $DISTRO_DIR $DEV_DIR
echo "symlink php script in dev folder"
cd $DEV_DIR
ln -s hak_tinymce.php $DEV_DIR/hak_tinymce.php
cd ..
