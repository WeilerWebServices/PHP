package com.mostc.pftt.scenario;

import com.mostc.pftt.host.AHost;

/** Scenario for testing the pdo_odbc and odbc extensions against a Microsoft Access database. (NOT IMPLEMENTED)
 * 
 * Access is one of 3 supported databases for the odbc and pdo_odbc extensions (the other 2 are SQL Server and IBM's DB2. We don't support DB2).
 * 
 * @see MSSQLODBCScenario
 * @author Matt Ficken
 *
 */

public class MSAccessScenario extends ODBCScenario {

	public MSAccessScenario(EMSAccessVersion version, AHost host, String default_username, String default_password) {
		super(version, host, default_username, default_password);
	}
	
	public MSAccessScenario(AHost host, String default_username, String default_password) {
		this(EMSAccessVersion.DEFAULT, host, default_username, default_password);
	}
	
	public static enum EMSAccessVersion implements IDatabaseVersion {
		DEFAULT {
				@Override
				public String getNameWithVersionInfo() {
					return "MSAccess";
				}
				@Override
				public boolean isAny() {
					return true;
				}
			}
	}

	@Override
	protected DatabaseScenarioSetup createScenarioSetup(boolean is_production_server) {
		return null;
	}

	@Override
	public boolean isImplemented() {
		return false;
	}

	@Override
	protected String getDriverClassName() {
		// TODO Auto-generated method stub
		return null;
	}

}
