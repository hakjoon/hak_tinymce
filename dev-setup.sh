#!/bin/bash

CURRENT_DIR=$(pwd)
DEV_DIR="$CURRENT_DIR/dev"
TINYMCE_DIR="tiny_mce"
DISTRO_DIR="$CURRENT_DIR/tinymce-distro/jscripts/$TINYMCE_DIR"
COMPRESSOR_DIR="$CURRENT_DIR/tinymce_compressor_php"

echo "removing old dev folder "
rm -r $DEV_DIR
echo "creating new dev folder"
mkdir $DEV_DIR
echo "copying distro folder to dev root"
cp -r $DISTRO_DIR $DEV_DIR
echo "copying compressor to dev dir"
cp $COMPRESSOR_DIR/tiny_mce_gzip.* $DEV_DIR/$TINYMCE_DIR
echo "symlink php script in dev folder"
#cd $DEV_DIR
ln -s $CURRENT_DIR/hak_tinymce.php $DEV_DIR/hak_tinymce.php
#cd ..
