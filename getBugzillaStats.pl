#!/usr/bin/perl

  my $DOWNLOAD_DIR="./bugzilla";
  my ($filename,$date) = @ARGV;

  #if date, download and return
  #if no date just retrieve filename

  if(!-d $DOWNLOAD_DIR){
    mkdir($DOWNLOAD_DIR);
  }

  if($date){  
    print wget(getURL($date));
  }else{
    open(file,"$DOWNLOAD_DIR/$filename.csv");
    my $info = join('',<file>);
    close(file);  
    print $info;
  }

sub wget{
  my $URL = $_[0];
  `wget -O $DOWNLOAD_DIR/$filename.csv "$URL"  /dev/null 2>&1`;
  open(file,"$DOWNLOAD_DIR/$filename.csv");
  my $info = join('',<file>);
  close(file);  
  return $info;
}

sub getURL{
  my $date=$_[0];
  my $URL = qq~http://bugs.winehq.org/report.cgi?bug_file_loc=&bug_file_loc_type=allwordssubstr&bug_id=&bug_status=UNCONFIRMED&bug_status=NEW&bug_status=ASSIGNED&bug_status=REOPENED&bug_status=RESOLVED&bug_status=CLOSED&bugidtype=include&chfieldfrom=&chfieldto=~;
  $URL .= qq~$date~;
  $URL .= qq~&chfieldvalue=&email1=&email2=&emailassigned_to1=1&emailassigned_to2=1&emailcc2=1&emailreporter2=1&emailtype1=substring&emailtype2=substring&field0-0-0=%255BBug%2Bcreation%255D&keywords=&keywords_type=allwords&long_desc=&long_desc_type=substring&short_desc=&short_desc_type=allwordssubstr&status_whiteboard=&status_whiteboard_type=allwordssubstr&type0-0-0=noop&value0-0-0=&votes=&x_axis_field=&y_axis_field=bug_status&z_axis_field=&width=600&height=350&action=wrap&ctype=csv&format=table~;
  return $URL;
}
