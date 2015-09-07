Note: Currently kml, placemark, gpx, and wpt scaffold files in this folder are not being used.
they are intended to replace GpxWriter, and KmlWriter functions so that those classes will 
not be neccessary. Mainly I want to remove the use of DOMDocument since the entire structure is
held in memory it is not optimal for generating large documents quickly in my opinion
