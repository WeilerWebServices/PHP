package com.mostc.pftt.scenario;

import java.sql.Connection;
import java.sql.DriverManager;
import java.sql.SQLException;
import java.sql.Statement;
import java.util.Map;

import com.mostc.pftt.host.AHost;
import com.mostc.pftt.host.AHost.ExecHandle;
import com.mostc.pftt.model.core.PhpBuild;
import com.mostc.pftt.model.core.PhpIni;
import com.mostc.pftt.model.sapi.WebServerManager;
import com.mostc.pftt.results.ConsoleManager;
import com.mostc.pftt.results.ConsoleManagerUtil;
import com.mostc.pftt.results.EPrintType;

/** Sets up a MySQL database and tests the mysql, mysqli and pdo_mysql extensions against it.
 * 
 * @author Matt Ficken
 *
 */
 
public class MySQLScenario extends DatabaseScenario {
	public static final int DEFAULT_MYSQL_PORT = 3306;
	public static final String DEFAULT_USERNAME = "root";
	public static final String DEFAULT_PASSWORD = "password01!";//root";// TODO temp azure password01!";
	
	public MySQLScenario(EMySQLVersion version, AHost host, String default_username, String default_password) {
		super(version, host, default_username, default_password);
	}
		
	public MySQLScenario(EMySQLVersion version, AHost host) {
		this(version, host, DEFAULT_USERNAME, DEFAULT_PASSWORD);
	}
	
	public MySQLScenario(AHost host, String default_username, String default_password) {
		this(EMySQLVersion.DEFAULT, host, default_username, default_password);
	}
		
	public MySQLScenario(AHost host) {
		this(host, DEFAULT_USERNAME, DEFAULT_PASSWORD);
	}
	
	public static enum EMySQLVersion implements IDatabaseVersion {
		DEFAULT {
			public String getNameWithVersionInfo() {
				return "MySQL-5.6"; // TODO autodetect
			}
			@Override
			public boolean isAny() {
				return false;
			}
		};
	}
	
	@Override
	public boolean isImplemented() {
		return true;
	}
	
	@Override
	protected MySQLScenarioSetup createScenarioSetup(boolean is_production_server) {
		return new MySQLScenarioSetup();
	}
		
	public class MySQLScenarioSetup extends DefaultDatabaseScenarioSetup {
		protected ExecHandle mysqld_handle;
		protected String datadir, hostname;
		
		
		public MySQLScenarioSetup() {
			hostname = host.getHostname();
			port = DEFAULT_MYSQL_PORT;
		}
		
		@Override
		public boolean databaseExists(String db_name) {
			return empty(executeQuery("SHOW DATABASES LIKE '"+db_name+"'"));
		}
		
		@Override
		public boolean createDatabaseWithUser(String db_name, String user, String password) {
			return createDatabase(db_name) &&
					execute("GRANT ALL ON "+db_name+".* TO `"+user+"`@`localhost` IDENTIFIED BY '"+password+"'") &&
					execute("GRANT ALL ON "+db_name+".* TO `"+user+"`@'%' IDENTIFIED BY '"+password+"'") &&
					execute("GRANT ALL ON "+db_name+".* TO `"+user+"` IDENTIFIED BY '"+password+"'");
		}
		
		@Override
		public boolean createDatabaseReplaceOk(String db_name) {
			execute("DROP DATABASE IF EXISTS "+db_name);
			return createDatabase(db_name);
		}
		
		@Override
		public boolean createDatabaseWithUserReplaceOk(String db_name, String user, String password) {
			// GRANT ALL PRIVILEGES ON *.* TO 'root' IDENTIFIED BY 'password';
			// GRANT ALL PRIVILEGES ON *.* TO 'user'.'%' IDENTIFIED BY 'password';
			return createDatabaseReplaceOk(db_name) &&
					
					// TODO temp
					execute("GRANT ALL ON "+db_name+".* TO `root`@`%` IDENTIFIED BY '"+password+"'") &&
					execute("GRANT ALL ON "+db_name+".* TO `root`@ IDENTIFIED BY '"+password+"'") &&
					execute("GRANT ALL ON "+db_name+".* TO `root`@`localhost` IDENTIFIED BY '"+password+"'") &&
					
					execute("GRANT ALL ON "+db_name+".* TO `wp_test`@`%` IDENTIFIED BY '"+password+"'") &&
					execute("GRANT ALL ON "+db_name+".* TO `wp_test`@ IDENTIFIED BY '"+password+"'") &&
					execute("GRANT ALL ON "+db_name+".* TO `wp_test`@`localhost` IDENTIFIED BY '"+password+"'")
					
					;
					//execute("GRANT ALL ON "+db_name+".* TO `"+user+"`@`localhost` IDENTIFIED BY '"+password+"'") &&
					//execute("GRANT ALL ON "+db_name+".* TO `"+user+"`@`%` IDENTIFIED BY '"+password+"'") &&
					//execute("GRANT ALL ON "+db_name+".* TO `"+user+"`@ IDENTIFIED BY '"+password+"'");
					//execute("GRANT ALL ON "+db_name+".* TO `"+user+"`@`%` IDENTIFIED BY '"+password+"'") &&
					//execute("GRANT ALL ON "+db_name+".* TO `"+user+"` IDENTIFIED BY '"+password+"'");
		}
		
		@Override
		protected Connection createConnection() throws SQLException { 
			// TODO temp azure 
			String url = "jdbc:mysql://"+getHostname()+":"+getPort()+"/?user="+getUsername()+"&password="+getPassword();
			//String url = "jdbc:mysql://localhost/?user="+getUsername()+"&password="+getPassword();

			return DriverManager.getConnection(url);
		}

		@Override
		public boolean prepareINI(ConsoleManager cm, FileSystemScenario fs, AHost host, PhpBuild build, ScenarioSet scenario_set, PhpIni ini) {
			return ini.addExtensionAndCheck(cm, fs, host, null, build, PhpIni.EXT_MYSQLI)
				// TODO temp 1/14 && ini.addExtensionAndCheck(cm, fs, host, null, build, PhpIni.EXT_MYSQL)
				&& ini.addExtensionAndCheck(cm, fs, host, null, build, PhpIni.EXT_PDO_MYSQL);
		}
		
		/** environment variables for running PHPT or PhpUnit tests
		 * 
		 */
		@Override
		public void getENV(Map<String, String> env) {
			String dsn = getDataSourceName();
			// PHPT tests use environment variables to get configuration information
			// vars for ext/mysql and ext/mysqli
			env.put("MYSQL_TEST_HOST", getHostname());
			env.put("MYSQL_TEST_PORT", Integer.toString(getPort()));
			env.put("MYSQL_TEST_USER", getUsername());
			env.put("MYSQL_TEST_PASSWD", getPassword());
			env.put("MYSQL_TEST_DB", getDatabaseName());
			env.put("MYSQL_TEST_DSN", dsn);
			// vars for ext/pdo_mysql
			//
			// NOTE: ext/pdo/tests/pdo_test.inc originally uses getenv(), but needs to use $_ENV here
			//       otherwise it won't get these ENV vars on Apache (or IIS? probably.)
			env.put("PDO_MYSQL_TEST_HOST", getHostname());
			env.put("PDO_MYSQL_TEST_PORT", Integer.toString(getPort()));
			env.put("PDO_MYSQL_TEST_USER", getUsername());
			env.put("PDO_MYSQL_TEST_PASSWD", getPassword());
			env.put("PDO_MYSQL_TEST_PASS", getPassword());
			env.put("PDO_MYSQL_TEST_DB", getDatabaseName());
			env.put("PDOTEST_USER", getUsername());
			env.put("PDOTEST_PASS", getPassword());
			env.put("PDOTEST_DSN", dsn);
		}
		
		/** entries for $_GLOBALS for PhpUnit tests
		 * 
		 * these entries are also defined with define() calls in PHP
		 * 
		 */
		@Override
		public void setGlobals(Map<String, String> globals) {
			super.setGlobals(globals);
			// @see joomla-platform/tests/core/case/database/mysql.php
			globals.put("JTEST_DATABASE_MYSQL_DSN", getDataSourceName());
			globals.put("JTEST_DATABASE_MYSQLI_DSN", getDataSourceName());
		}

		@Override
		public String getDataSourceName() {
			return "mysql:host="+getHostname()+";port="+getPort()+";dbname="+getDatabaseName()+";user="+getUsername()+";pass="+getPassword();
		}

		@Override
		public String getPdoDbType() {
			return "pdo_mysql";
		}
		public String getHostname() {
			return hostname;
		}
		
		@Override
		protected boolean startServer(ConsoleManager cm, boolean is_production_server) {
			try {
				if (is_production_server) {
					// TODO set tmpdir=C:/ProgramData/MySQL/temp in my.ini
					//      otherwise will randomly fail to startup
					cm.println(EPrintType.IN_PROGRESS, getClass(), "Starting MySQL Windows Service for production use...");
					port = DEFAULT_MYSQL_PORT;
					hostname = host.getAddress();
					host.execElevated(cm, getClass(), "net start MySQL56", AHost.ONE_MINUTE);
				} else {
					// TODO improve comment
					// -delete temporary test databases
					// -some tests seem to corrupt databases preventing mysql from being started again
					//    (if using the same data dir, which is what happens with `net start MySQL56`)
					String mysql_dir = host.getSystemDrive()+"\\Program Files\\MySQL\\MySQL Server 5.6";
					
					cm.println(EPrintType.IN_PROGRESS, getClass(), "Using MySQL install: "+mysql_dir);
					cm.println(EPrintType.IN_PROGRESS, getClass(), "Creating temporary MySQL datadir");
					
					datadir = host.mCreateTempName(getClass());
					host.mCreateDirs(datadir);
					
					cm.println(EPrintType.IN_PROGRESS, getClass(), "Copying template datadir to temporary MySQL datadir "+datadir);
					host.mCopy(mysql_dir+"\\data", datadir);
					
					hostname = "127.0.0.1";
					boolean chose = false;
					for(int i = DEFAULT_MYSQL_PORT+1 ; i < 65535 ; i++ ) {
						if (!WebServerManager.isLocalhostTCPPortUsed(i)) {
							port = i;
							chose = true;
							break;
						}
					}
					if (!chose) {
						cm.println(EPrintType.CANT_CONTINUE, getClass(), "Could not find unused TCP port for MySQL server");
						return false;
					}
					final String cmd = "\""+mysql_dir+"\\bin\\mysqld\" --standalone --console --bind-address="+hostname+" --port="+port+" --datadir="+datadir;
					
					cm.println(EPrintType.IN_PROGRESS, getClass(), "Starting MySQL in standalone mode (directly, using default config)...");
					mysqld_handle = ((AHost)host).execThread(cmd, null, null, null, true);
					
					// wait for server to output that is running before checking below
					while (mysqld_handle.isRunning()) {
						if (mysqld_handle.getOutput(50).contains("Server hostname (bind-address)"))
							break;
					}
				} // end if
				
				cm.println(EPrintType.IN_PROGRESS, getClass(), "Checking if MySQL server is connectable...");
				// make sure mysql can be connected to
				if (WebServerManager.isLocalhostTCPPortUsed(port)) {
					cm.println(EPrintType.IN_PROGRESS, getClass(), "MySQL server is connectable");
					
					if (!is_production_server) {
						// TODO comment
						cm.println(EPrintType.IN_PROGRESS, getClass(), "Configuring "+getUsername()+" user on MySQL server");
						
						String url = "jdbc:mysql://"+getHostname()+":"+getPort()+"/?user="+getUsername();
						
						Connection c = DriverManager.getConnection(url);
						
						Statement s = c.createStatement();
						s.execute("use mysql");
						// TODO update password for specific user?
						s.execute("UPDATE user SET password=PASSWORD('"+getPassword()+"')");
						s.execute("flush privileges");
					}
					
					return true;
				}
				cm.println(EPrintType.CLUE, getClass(), "MySQL server is not connectable");
			} catch (Exception ex) {
				ConsoleManagerUtil.printStackTrace(MySQLScenario.class, cm, ex);
				
				if (mysqld_handle!=null)
					mysqld_handle.close(cm, true);
			}
			cm.println(EPrintType.CANT_CONTINUE, getClass(), "Failed to start MySQL server");
			return false;
		} // end protected boolean startServer
		
		protected boolean cleanupServerAfterFailedStarted(ConsoleManager cm, boolean is_production_server) {
			try {
				stopServerEx(cm, is_production_server);
				return true;
			} catch ( Exception ex ) {
				ConsoleManagerUtil.printStackTrace(MySQLScenario.class, cm, ex);
			}
			return false;
		}
		
		protected void stopServerEx(ConsoleManager cm, boolean is_production_server) throws Exception {
			if (is_production_server) {
				cm.println(EPrintType.IN_PROGRESS, getClass(), "Stopping production MySQL Windows Service...");
				host.execElevated(cm, getClass(), "net stop MySQL56", AHost.ONE_MINUTE);
			} else {
				cm.println(EPrintType.IN_PROGRESS, getClass(), "Stopping MySQL standalone process");
				if (mysqld_handle!=null)
					mysqld_handle.close(cm, true);
			}
		}
		
		@Override
		protected boolean stopServer(ConsoleManager cm, boolean is_production_server) {
			try {
				stopServerEx(cm, is_production_server);
				
				// wait until MySQL can't be connected to
				if (!WebServerManager.isLocalhostTCPPortUsed(getPort())) {
					if (!is_production_server) {
						if (datadir!=null && !cm.isPfttDebug() && (!cm.isDebugAll()||!cm.isDebugList())) {
							cm.println(EPrintType.IN_PROGRESS, getClass(), "Deleting temporary MySQL datadir");
							host.mDelete(datadir);
							datadir = null;
						}
					}
					cm.println(EPrintType.IN_PROGRESS, getClass(), "Stopped MySQL server");
				}
				return true;
			} catch (Exception ex) {
				ConsoleManagerUtil.printStackTrace(MySQLScenario.class, cm, ex);
			}
			cm.println(EPrintType.CANT_CONTINUE, getClass(), "Unable to stop MySQL server");
			return false;
		} // end protected boolean stopServer
		
	} // end public class MySQLScenarioSetup

	@Override
	protected String getDriverClassName() {
		return "com.mysql.jdbc.Driver";
	}
	
} // end public class MySQLScenario
