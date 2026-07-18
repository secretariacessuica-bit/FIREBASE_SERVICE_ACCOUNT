import ftplib
import ssl

print("Testing FTPS via Python...")
try:
    ftp = ftplib.FTP_TLS()
    ftp.connect('ftp.infomaniak.com', 21, timeout=10)
    ftp.login('6o9v7p_admin', 'Ces124578.')
    ftp.prot_p()  # Set up secure data connection
    files = ftp.nlst()
    print("Success! Files in root:")
    for f in files:
        print(f)
        
    try:
        ftp.cwd('sites')
        files = ftp.nlst()
        print("Success! Files in /sites:")
        for f in files:
            print(f)
    except:
        pass

    ftp.quit()
except Exception as e:
    print("Failed:", str(e))
