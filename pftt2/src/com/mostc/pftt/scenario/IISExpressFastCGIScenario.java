package com.mostc.pftt.scenario;

/** Runs PHP under IIS-Express using FastCGI (NOT IMPLEMENTED)
 * 
 * @author Matt Ficken
 *
 */

public class IISExpressFastCGIScenario extends IISFastCGIScenario {

	@Override
	public String getName() {
		return "IIS-Express-FastCGI";
	}
	
	@Override
	public boolean isImplemented() {
		return false;
	}

	@Override
	public boolean isExpress() {
		return true;
	}

}
