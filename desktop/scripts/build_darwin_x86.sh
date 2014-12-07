#!/bin/bash

ARCHIVE_VERSION="node-webkit-v0.11.2-osx-ia32"
BUILD_DIR="build/node-webkit-osx-x86"

if [ ! -f "tmp/node-webkit-osx-x86.zip" ]; then
    echo "Downloading archive..."
    wget http://dl.node-webkit.org/v0.11.2/${ARCHIVE_VERSION}.zip -O tmp/node-webkit-osx-x64.zip -q
fi

echo "Cleaning build directory..."
rm -rf $BUILD_DIR/*

echo "Build start..."
unzip tmp/node-webkit-osx-x64.zip -d $BUILD_DIR/
mv $BUILD_DIR/$ARCHIVE_VERSION/* $BUILD_DIR/
rm -r $BUILD_DIR/$ARCHIVE_VERSION

if [ ! -d "$BUILD_DIR/node-webkit.app/Contents/Resources/app.nw" ]; then
    mkdir -p $BUILD_DIR/node-webkit.app/Contents/Resources/app.nw
fi

cp -f index.html $BUILD_DIR/node-webkit.app/Contents/Resources/app.nw/
cp -f package.json $BUILD_DIR/node-webkit.app/Contents/Resources/app.nw/
cp -Rf images $BUILD_DIR/node-webkit.app/Contents/Resources/app.nw/

echo "Build success! Open $BUILD_DIR on your finder"
