
def describe() {
	"Uses the File Deduplication feature of Windows Server 2012+, over the SMB remote file system"
}

def scenarios() {
	evaluate(new File("$__DIR__/CommonConfig.groovy"));
	
	new SMBDeduplicationScenario(CommonConfig.getFileServerHost(), CommonConfig.getFileServerVolume())
}
