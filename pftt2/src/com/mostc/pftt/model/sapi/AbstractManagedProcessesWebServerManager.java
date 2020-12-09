package com.mostc.pftt.model.sapi;

import java.io.FileNotFoundException;
import java.io.IOException;
import java.net.InetSocketAddress;
import java.net.Socket;
import java.net.URL;
import java.net.URLConnection;
import java.util.Map;
import java.util.Timer;
import java.util.TimerTask;
import java.util.concurrent.atomic.AtomicInteger;

import com.github.mattficken.Overridable;
import com.mostc.pftt.host.AHost;
import com.mostc.pftt.host.LocalHost;
import com.mostc.pftt.host.AHost.ExecHandle;
import com.mostc.pftt.model.core.PhpBuild;
import com.mostc.pftt.model.core.PhpIni;
import com.mostc.pftt.results.ConsoleManager;
import com.mostc.pftt.results.ConsoleManagerUtil;
import com.mostc.pftt.runner.AbstractPhptTestCaseRunner;
import com.mostc.pftt.scenario.FileSystemScenario;
import com.mostc.pftt.scenario.ScenarioSet;
import com.mostc.pftt.util.DebuggerManager;
import com.mostc.pftt.util.DebuggerManager.Debugger;
import com.mostc.pftt.util.TimerUtil.RepeatingThread;
import com.mostc.pftt.util.TimerUtil;
import com.mostc.pftt.util.WinDebugManager;

public abstract class AbstractManagedProcessesWebServerManager extends WebServerManager {
	protected static final int PORT_RANGE_START = 40000;
	// over 49151 may be used by client side of tcp sockets or other dynamic uses
	// Windows seems to start at a lower range (44000+)
	protected static final int PORT_RANGE_STOP = 44000;
	//
	protected AtomicInteger last_port = new AtomicInteger(PORT_RANGE_START-1);
	//
	protected final Timer timer;
	protected final DebuggerManager dbg_mgr;
	
	public AbstractManagedProcessesWebServerManager() {
		timer = new Timer();
		
		dbg_mgr = new WinDebugManager();
		
		if (LocalHost.isLocalhostWindows()) {
			Runtime.getRuntime().addShutdownHook(new Thread() {
				@Override
				public void run() {
					// Windows BN: may leave `php.exe -S` or Apache running if they aren't terminated here
					// NOTE: if you click stop in Eclipse, you won't get here
					// on Windows, that means that `php.exe -S` instances will still be running
					// you'll need to do: taskkill /im:php.exe /f /t
					// or: taskkill /im:httpd.exe /f /t
					close(null);
				}
			});
		}
	}
	
	public static class CouldConnect {
		public boolean connect = false;
		public long start_time;
		public int attempts;
	}
	
	/** makes sure the web server is running and accepting connections.
	 * 
	 * @param listen_address
	 * @param port
	 * @param double_sure - if TRUE, sends HTTP request, if FALSE just does TCP socket.
	 * 	                TCP (!double_sure) is much faster, HTTP is much slower (only use HTTP when you think it might not work)
	 * @return
	 */
	public static CouldConnect canConnect(String listen_address, int port, boolean double_sure) {
		Socket sock = null;
		CouldConnect could = new CouldConnect();
		could.start_time = System.currentTimeMillis();
		
		if (double_sure) {
			for ( could.attempts=0 ; could.attempts < 10 ; could.attempts++ ) {
				could.connect = false;
				
				try {
					final int timeout = Math.max(200, Math.min(60000, 100*((int)(Math.pow(could.attempts+1, could.attempts+1)))));
					
					URL url = new URL("http://"+listen_address+":"+port+"/");
					URLConnection uc = url.openConnection();
					uc.setConnectTimeout(timeout);
					uc.setReadTimeout(timeout);
					uc.connect();
					uc.getInputStream();
						
					could.connect = true;
					return could;
				} catch ( FileNotFoundException ex ) {
					// got HTTP 404 response from web server - which means its up and responding to HTTP requests
					// (regardless of whether or not that page actually exists)
					could.connect = true;
					return could;
				} catch ( IOException ex ) {
					// did not get HTTP response (may not have been able to do TCP connect/accept even)
				}
			}
		} else {
			// builtin web server needs many attempts and large timeouts
			for ( could.attempts=0 ; could.attempts < 10 ; could.attempts++ ) {
				could.connect = false;
				try {
					sock = new Socket();
					sock.setSoTimeout(Math.max(200, Math.min(60000, 100*((int)(Math.pow(could.attempts+1, could.attempts+1))))));
					sock.connect(new InetSocketAddress(listen_address, port));
					if (sock.isConnected()) {
						could.connect = true;
						return could;
					}
				} catch ( IOException ex ) {
				} finally {
					try {
						sock.close();
					} catch (IOException e) {
					}
				}
			}
		}
		could.connect = false; // ensure
		return could;
	} // end public static CouldConnect canConnect
	
	static final int MAX_TOTAL_ATTEMPTS = 3;
	@Override
	protected WebServerInstance createWebServerInstance(ConsoleManager cm, FileSystemScenario fs, AHost host, ScenarioSet scenario_set, PhpBuild build, PhpIni ini, Map<String,String> env, final String docroot, final boolean debugger_attached, final Object server_name, boolean is_replacement) {
		String sapi_output = "";
		int port_attempts;
		boolean found_port;
		int total_attempts = 0;
		int port = PORT_RANGE_START;
		for ( ; total_attempts < MAX_TOTAL_ATTEMPTS ; total_attempts++) {
			
			// find port number not currently in use
			port_attempts = 0;
			found_port = false;
			// important: don't wrap this method, for or while loops in a synchronize
			//            it'll block things up too much between threads (will get ~1 httpd.exe for every 4 threads)
			//            instead, just use an AtomicInteger to coordinate port allocation
			while (port_attempts < 3) {
				port = last_port.incrementAndGet();
				if (port > PORT_RANGE_STOP) {
					// start over and hope ports at start of range are free
					last_port.set(PORT_RANGE_START);
					port_attempts++;
				} else if (!isLocalhostTCPPortUsed(port)) {
					found_port = true;
					break;
				}
			}
			
			if (!found_port) {
				// try again
				sapi_output += "PFTT: Couldn't find unused local port\n";
				continue;
			}
			
			// Windows BN: php -S won't accept connections if it listens on localhost|127.0.0.1 on Windows
			//             php won't complain about this though, it will run, but be inaccessible
			String listen_address = host.getLocalhostListenAddress();
			
			AHost.ExecHandle handle = null;
			try {
				// provide a ManagedProcessWebServerInstance to handle the running web server instance
				final ManagedProcessWebServerInstance web = createManagedProcessWebServerInstance(cm, fs, host, scenario_set, build, ini, env, docroot, listen_address, port);
				if (web==null)
					break; // fall through to returning CrashedWebServerInstance
				
				handle = host.execThread(web.getCmdString(), web.getEnv(), docroot, false);
				
				final AHost.ExecHandle handlef = handle;
								
				// ensure server can be connected to
				//
				// only use HTTP to check, if its a replacement web server
				// this makes a real difference in test speed: use TCP only for the first web server
				//    (since most of the time it will work, web servers that have to be replaced are the ones
				//     to likely have a problem)
				CouldConnect could = _canConnect(listen_address, port, is_replacement); 
				if (!could.connect) {
					// kill server and try again
					throw new IOException("Could not socket to web server after it was started. Web server did not respond to socket. Tried "+could.attempts+" times, waiting "+(System.currentTimeMillis()-could.start_time)+" millis total.");
				}
				//
				//
				// if -debug_all or -debug_list console option, run web server with windebug
				if (debugger_attached) {
					waitIfTooManyActiveDebuggers();
					
					web.debug_handle = dbg_mgr.newDebugger(cm, host, scenario_set, server_name, build, (LocalHost.LocalExecHandle) handle);
					
					// SPEC: see run-test.php line 1487
					if (false) { // TODO cm.isMemCheck()) {
						env.put(AbstractPhptTestCaseRunner.ENV_USE_ZEND_ALLOC, "0");
						env.put(AbstractPhptTestCaseRunner.ENV_ZEND_DONT_UNLOAD_MODULES, "1");
					} else {
						env.put(AbstractPhptTestCaseRunner.ENV_USE_ZEND_ALLOC, "1");
						env.put(AbstractPhptTestCaseRunner.ENV_ZEND_DONT_UNLOAD_MODULES, "0");
					}
				}
				//
				
				web.setProcess(handle);
				
				// check web server every 1 second to see if it has crashed
				TimerUtil.repeatEverySeconds(1, new TimerUtil.RepeatingRunnable() {
						@Override
						public void run(RepeatingThread thread) {
							if (!handlef.isRunning()) {
								try {
									if (handlef.isCrashed()) {
										String output_str;
										try {
											output_str = handlef.getOutput();
										} catch ( Exception ex ) {
											output_str = ConsoleManagerUtil.toString(ex);
										}
										// notify of web server crash
										//
										// provide output and exit code
										web.notifyCrash(output_str, handlef.getExitCode());
									}
								} finally {
									// don't need to check any more
									thread.close();
								}
							}
						} // end public void run
					});
				//
				
				return web;
			} catch ( Exception ex ) {
				if (handle!=null && !handle.isCrashed())
					// make sure process is killed in this case (don't kill crashed process)
					handle.close(cm);
				
				sapi_output += ConsoleManagerUtil.toString(ex) + "\n";
			}
		} // end for
		
		// fallback
		sapi_output = "PFTT: could not start web server instance (after "+total_attempts+" attempts)... giving up.\n" + sapi_output;
		
		// return this failure message to client code
		return new CrashedWebServerInstance(fs, host, this, ini, env, sapi_output);
	} // end protected WebServerInstance createWebServerInstance
	
	@Overridable
	protected CouldConnect _canConnect(String listen_address, int port, boolean is_replacement) {
		return canConnect(listen_address, port, is_replacement);
	}

	protected abstract ManagedProcessWebServerInstance createManagedProcessWebServerInstance(ConsoleManager cm, FileSystemScenario fs, AHost host, ScenarioSet scenario_set, PhpBuild build, PhpIni ini, Map<String, String> env, String docroot, String listen_address, int port);
	
	public abstract class ManagedProcessWebServerInstance extends WebServerInstance {
		protected Debugger debug_handle;
		protected final int port;
		protected final String hostname, cmd;
		protected final String docroot;
		protected ExecHandle process;
		
		public ManagedProcessWebServerInstance(FileSystemScenario fs, AHost host, AbstractManagedProcessesWebServerManager ws_mgr, String docroot, String cmd, PhpIni ini, Map<String,String> env, String hostname, int port) {
			super(fs, host, ws_mgr, LocalHost.splitCmdString(cmd), ini, env);
			this.docroot = docroot;
			this.cmd = cmd;
			this.hostname = hostname;
			this.port = port;
		}
		
		@Override
		public void notifyCrash(String output, int exit_code) {
			if (output==null)
				output = "PFTT: web server started with: "+cmd;
			else
				output = "PFTT: web server started with: "+cmd + "\n" + output;
			super.notifyCrash(output, exit_code);
		}
		
		@Override
		public String getDocroot() {
			return docroot;
		}
		
		@Override
		public String toString() {
			return hostname+":"+port;
		}
		
		public String getCmdString() {
			return cmd;
		}

		public void setProcess(ExecHandle handle) {
			this.process = handle;
		}

		@Override
		public String getHostname() {
			return hostname;
		}

		@Override
		public int getPort() {
			return port;
		}
		
		@Override
		public boolean isCrashedOrDebuggedAndClosed() {
			return super.isCrashedOrDebuggedAndClosed() || process.isCrashedOrDebuggedAndClosed();
		}
		
		@Override
		public boolean isCrashedAndDebugged() {
			return process.isCrashedAndDebugged();
		}
		
		@Override
		public boolean isDebuggerAttached() {
			return debug_handle != null && debug_handle.isRunning();
		}

		private boolean waiting_for_debug_of_crashed_process;
		@Override
		protected void do_close(ConsoleManager cm) {
			if (isCrashedOrDebuggedAndClosed() && debug_handle!=null) {
				if (waiting_for_debug_of_crashed_process)
					return;
				waiting_for_debug_of_crashed_process = true;
				
				// leave process running so it can be debugged
				// keep track of debugger/how many debuggers are attached to
				// a crashed process
				active_debugger_count.incrementAndGet();
				timer.scheduleAtFixedRate(new TimerTask() {
						public void run() {
							if (!debug_handle.isRunning()) {
								waiting_for_debug_of_crashed_process = false;
								
								active_debugger_count.decrementAndGet();
								
								cancel();
							}
						}
					}, 1000, 1000);
				return;
			} else if (debug_handle!=null) {
				// process didn't crash, close debugger
				debug_handle.close(cm);
			}
			process.close(cm, true);
		}

		@Override
		public boolean isRunning() {
			return process.isRunning() && !isCrashedOrDebuggedAndClosed();
		}
		
	} // end public static abstract class ManagedProcessWebServerInstance
	
	private static AtomicInteger active_debugger_count = new AtomicInteger();
	private static int max_active_debuggers;
	static {
		max_active_debuggers = Math.max(2, Math.min(8, LocalHost.getInstance().getCPUCount()));
	}
	
	/** can have only a limited number of debuggers running, this will
	 * block if there are too many running.
	 * 
	 * @throws InterruptedException
	 */
	public static void waitIfTooManyActiveDebuggers() throws InterruptedException {
		while (AbstractManagedProcessesWebServerManager.active_debugger_count.get() >= max_active_debuggers) {
			Thread.sleep(100);
		}
	}
	
} // end public abstract class AbstractManagedProcessesWebServerManager
