
def scenarios() {
	new PhpMyAdminScenario()
}

def describe() {
	"Load PhpMyAdmin application"
}

class PhpMyAdminPhpUnitTestPack extends RequiredDatabasePhpUnitSourceTestPack {
	
	@Override
	public String getNameAndVersionString() {
		return "PhpMyAdmin";
	}
	
	@Override
	protected String getSourceRoot(ConsoleManager cm, AHost host) {
		return host.getPfttDir()+"/cache/working/phpmyadmin";
	}
	
	@Override
	public boolean isDevelopment() {
		return true;
	}
	
	@Override
	public boolean isFileNameATest(String file_name) {
		return file_name.endsWith(".php");
	}
	
	@Override
	protected boolean openAfterInstall(ConsoleManager cm, AHost host) throws Exception {
		
		addIncludeDirectory(getRoot()+"/libraries");
		addPhpUnitDist(getRoot()+"/test/", getRoot()+"/test/bootstrap-dist.php");
		
		return true;
	}
	
} // end class PhpMyAdminPhpUnitTestPack

/*def getPhpUnitSourceTestPack() {
	new PhpMyAdminPhpUnitTestPack();
}*/
