////////////////////////////////////////////////////////
//
// GEM - Graphics Environment for Multimedia
//
// zmoelnig@iem.kug.ac.at
//
// Implementation file
//
//    Copyright (c) 2011-2011 IOhannes m zmölnig. forum::für::umläute. IEM. zmoelnig@iem.at
//    For information on usage and redistribution, and for a DISCLAIMER OF ALL
//    WARRANTIES, see the file, "GEM.LICENSE.TERMS" in this distribution.
//
/////////////////////////////////////////////////////////
  
#include "imageloader.h"
#include "plugins/PluginFactory.h"

gem::plugins::imageloader :: ~imageloader(void) {}

gem::plugins::imageloader*gem::plugins::imageloader::getInstance(void) {
 return NULL;
}

static gem::PluginFactoryRegistrar::dummy<gem::plugins::imageloader> fac_imageloaderdummy;
