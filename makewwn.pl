#!/usr/bin/perl

my $SCRIPT_FOLDER = "."; #contans stat.gen and kcstats2
my $WWN_DIR       = "wwn";
my %FORM;
print "Content-Type: text/html\n\n";

print qq~
<html>
<head> 
<title>WWN Creator by Zachary Goldberg</title>
</head>
<body>
<center>
<h1><a href="$0"><font color="black" style="text-decoration: none;">World Wine Newsletter Creator</font></a></h1>
<br>
~;
parse_form();

if(!$ENV{QUERY_STRING}){
  &chose_wwn;
}elsif($FORM{a} eq "new"){
  &new_wwn;
}elsif($FORM{a} eq "create"){
  &create_wwn;
  &edit_wwn;
}elsif($FORM{a} eq "save"){
  &save_wwn;
  &edit_wwn;
}elsif($FORM{a} eq "statAsk"){
  &stat_gen_ask;
}elsif($FORM{a} eq "stat_gen"){
  &stat_gen;
  &edit_wwn;
}elsif($FORM{a} eq "viewDemo"){
  &makeDemo;
}else{
  &edit_wwn;
}
sub makeDemo{
  print qq~Prepping for demo and preping Git Commit~;
  ($sec,$min,$hour,$mday,$mon,$year,$wday,$yday,$isdst)=localtime(time);
  $year+=1900;
  $mon++;
  if($mday < 10){
   $mday = "0".$mday;
  }
  if($mon < 10){
   $mon = "0".$mon;
  }
  $file = "wn$year$mon$mday"."_"."$FORM{wwn}.xml";
  `cp $WWN_DIR/$FORM{wwn}/kcstats$FORM{wwn}.txt $WWN_DIR/$FORM{wwn}/$file`;
  `cat $WWN_DIR/$FORM{wwn}/sections$FORM{wwn}.xml >> $WWN_DIR/$FORM{wwn}/$file`;
  `cat $WWN_DIR/$FORM{wwn}/appstats.xml >> $WWN_DIR/$FORM{wwn}/$file`;  
  `echo "</kc>" >> $WWN_DIR/$FORM{wwn}/$file`;
  `sed -f s-sed.stat $WWN_DIR/$FORM{wwn}/$file > temp && cat temp > "$WWN_DIR/$FORM{wwn}/$file" && rm temp`;
   #remove "trailing whitespace" problems
   open(file,"$WWN_DIR/$FORM{wwn}/$file");
   my @data = <file>;
   close(file);
   #Remove trailing whitespace
   open(file,">$WWN_DIR/$FORM{wwn}/$file");
   foreach(@data){
     my $line = $_;
     $line =~ s/[ \t]*\n$//;
     $line =~ s/\&amp;lt;/\&lt;/g;
     $line =~ s/\&amp;gt;/\&gt;/g;
     print file $line."\n";
   }
   close(file);

  `rm WineHQ/wwn/*_$FORM{wwn}.xml`;
  `cp $WWN_DIR/$FORM{wwn}/$file WineHQ/wwn/en/$file`;
  `chmod o+r WineHQ/wwn/en/$file`;
  $results = `xmllint $WWN_DIR/$FORM{wwn}/$file 2>&1`;
  if($results =~ /parser error :/){
	print "XMLInt Errors:<br /><pre>$results</pre><br>";
	exit;
  }
  print qq~<a 
href="http://home.bluesata.com/WineWWN/WineHQ/?issue=$FORM{wwn}"> 
Demo 
Site</a> ~;

}
sub stat_gen{
  $cp = qq~cp -v "$WWN_DIR/$FORM{last}/$FORM{last}.txt" "$WWN_DIR/$FORM{wwn}/$FORM{last}.txt"~;
  print $cp."<br>";
  print `$cp`."<br>";
  $cp = qq~cp -v "$WWN_DIR/$FORM{last}/$FORM{last}wd.txt" "$WWN_DIR/$FORM{wwn}/$FORM{last}wd.txt"~;
  print $cp."<br>";
  print `$cp`."<br>";

  my $wwn = $FORM{wwn};

  if(!$FORM{span}){$FORM{span}="0";}
  $cmd = qq~
export AUTHORNAME="$FORM{name}" &&
export AUTHORURL="$FORM{authorurl}" &&
export WWNDIR="$WWN_DIR/$FORM{wwn}" &&
export THISISSUE="$FORM{wwn}" &&
export LASTISSUE="$FORM{last}" &&
export THISFORMALDATE="$FORM{date}" &&
export THISMAINGOAL="$FORM{goal}" && 
export THISMODIFER="$ending" &&
export WWNARCHIVEURL="$FORM{archivebase}" &&
export THISARCHIVE="$FORM{thismonth}" &&
export SPAN="$FORM{span}" &&
export SPANARCHIVE="$FORM{lastmonth}" &&
bash stat.gen 2>&1;
  ~;
  $results =  `$cmd`;
  $results =~ s/</\&lt;/g;
  $results =~ s/>/\&gt;/g;
  $results =~ s/\n/<br>/g;
  print $results."<br>";
  print "Generating Application Changes...<br>";
  ($sec,$min,$hour,$mday,$mon,$year,$wday,$yday,$isdst)=localtime(time);
  $mon++;
  $year+=1900;
  if($mday < 10){
   $mday = "0".$mday;
  }
  if($mon < 10){
   $mon = "0".$mon;
  }
  $to = "$year-$mon-$mday 00:00:00";
  #DAYS SINCE LAST ISSUE!!!!!!!!!!!!!!!!!!!!!!
  $mday-= 30;
  if($mday <= 0){
    $mon--;
    $mday = 31+$mday;
    if($mon < 1){
      $mon = 12;
      $year--;
    }
   }
  if(length($mday) < 2){
   $mday = "0".$mday;
  }
  if(length($mon) < 2){
   $mon = "0".$mon;
  }

  $from = "$year-$mon-$mday 00:00:00";
  print $from;
  $|=1;
  $data = `perl getAppChanges.pl "$from" "$to" "$wwn"`;
  open(file,">$WWN_DIR/$FORM{wwn}/appstats.xml");
  print file $data;
  close(file);

}

sub stat_gen_ask{
  ($sec,$min,$hour,$mday,$mon,$year,$wday,$yday,$isdst)=localtime(time);
  $year+=1900;
  $mon++;
  if ($mon < 10) { $mon = "0" . $mon; }
  if ($mday < 10) { $mday = "0" . $mday; }
  $date = "$mon/$mday/$year";
  $month=`date +"%B"`;
  $month=~s/[\n\r]//g;
  $month.=".txt.gz";
  $last = $FORM{wwn}-1;
  print qq~
   <form action="$0?a=stat_gen" method="POST">
   Author Name:<br>
   <input name="name" value="Zachary Goldberg"><br>
   Author URL:<br>
   <input name="authorurl" value="http://www.bluesata.com"><br>
   This Issue:<br>
   <input name="wwn" value="$FORM{wwn}"><Br>
   Last Issue:<br>
   <input name="last" value="$last"><Br>
   Formal Date:<br>
   <input name="date" value="$date"><Br>
   Main Goal (<i>Include ending punctuation</i>):<br>
   is to <input name="goal"><br>
   This Archive Base URL:<br>
   <input name="archivebase" value="http://www.winehq.com/pipermail/wine-devel/" size="50"><br>
   This Months Archive:<br>
   <input name="thismonth" value="$year-$month"><br>
   Spanning Months?<br>
   <input type="checkbox" name="span" value="1"><br>
   If spanning: last Month's Archive:<br>
   <input name="lastmonth">
   <br>
   <input type="submit" value="Generate skeleton WWN">
  ~;

}
sub save_wwn{
  my $file = "$WWN_DIR/$FORM{wwn}/sections$FORM{wwn}.xml";
  open(file,">$file");
  $header=$FORM{header};
  $header=~s/&ltt;/</g;
  $header=~s/&gtt;/>/g;
  $header=~s/&qtt;/"/g;
  print file $header;
  my @titles;
  LOOP: foreach(0..$FORM{count}){
    my $c = $_;
    if( length($FORM{"content$c"}) < 5 && length($FORM{"title$c"}) < 3){ next LOOP; }
    $title = $FORM{"title$c"};
    push(@titles, $title);
    $subject = $FORM{"subject$c"};
    $archive = $FORM{"archive$c"};
    $posts = $FORM{"posts$c"};
    $topic = $FORM{"topic$c"};
    $content = $FORM{"content$c"};
    $content =~ s/\n\r/\n/g;
    #use proper Br
    $content =~ s/<br>/<br \/>/ig;
    #Turn links into real links
    $content =~ s#[\n\r ](http:\/\/.*?)[ <\n\r]# <a href="\1">\1<\/a> #ig;
    $content =~ s/\&/\&amp;/g;
    $content =~ s/ψ/&#248;/g; 
    $content =~ s/φ/&#246;/g;
    $content =~ s/α/&#225;/g;
    $content =~ s/°/&#176;/g;
    $content =~ s/ι/&#233;/g;
    $content =~ s/[μν]/&#236;/g;

    my $data = qq~
<section 
	title="$title"
	subject="$subject"
	archive="$archive"
	posts="$posts"
>
<topic>$topic</topic>
$content</section>~;  
   print file $data;
  }
  close(file);
  `dos2unix $file`;

  my $index = "<ul>\n";
  foreach (@titles)
  {
     $index .= qq~<li><a href="{\$root}/wwn/$FORM{wwn}#$_">$_</a></li>\n~;
  }

  $index .= "</ul>";

  ($sec,$min,$hour,$mday,$mon,$year,$wday,$yday,$isdst)=localtime(time);

  $mon++;
  $year+=1900;
  if($mday < 10){
   $mday = "0".$mday;
  }
  if($mon < 10){
   $mon = "0".$mon;
  }
  $date = "$year$mon$mday"."01".".xml";
  
  
  open(file, "$WWN_DIR/$FORM{wwn}/$date");
  my $data = join('', <file>);
  close(file);

  $data =~ s/\<\!--MAINLINKS--\>.*\<\!--ENDMAINLINKS--\>/\<\!--MAINLINKS--\>$index\<\!--ENDMAINLINKS--\>/;
print $data;
  open(file, ">$WWN_DIR/$FORM{wwn}/$date");
  print file $data;
  close(file);

  my $out = `svn add wwn/*/*`."<br>";
  $out .= `svn add *`."<br>";
  $out .= `svn commit -m "auto commit from makewwn.pl" 2>&1`."<br>";
  $out =~ s/[\n]/<br>/g;
  print $out;
  print "<h3>Updated $file!</h3><br><br>";
 
}
sub edit_wwn{
  if(!$FORM{wwn}){
    print "No WWN number given";
    exit;
  }
  my $file = "$WWN_DIR/$FORM{wwn}/sections$FORM{wwn}.xml";
  open(file,$file);
  my $lines = join('',<file>); 
  close(file);    
  #I hate HATE manual parsing of XML but i don't feel like looking up
  #docs for a perl xml parser atm, so 2 or 3 lines of regx it is
  $lines =~ s#(\<\?xml.*</stats>)##s;
  my $header = $1;
  $lines =~ s/<\/kc>//;
  my @sections = split(/<section/i,$lines);
  my $c = 0;
  $header=~s/</&ltt;/g;
  $header=~s/>/&gtt;/g;
  $header=~s/"/&qtt;/g;
  print qq~<a href="$0?a=viewDemo&wwn=$FORM{wwn}">View this WWN On Demo Site</a><br><br>~;
  print qq~<a href="$0?a=statAsk&wwn=$FORM{wwn}">Generate Stats/Changed Apps for  $FORM{wwn}</a>
    <hr width="550" color="black">~;

  print qq~<form action="$0?a=save&wwn=$FORM{wwn}" method="POST">~;
  print qq~<input type=hidden name="header" value="$header">~;
  print qq~<input type=submit value="Edit WWN $FORM{wwn}">~;
  foreach $sec (@sections){
    if(length($sec) < 5){ next; }
    $sec =~ m/(.*?)>/s; #all the info in the section tag
    my $attributes = $1;
    $attributes =~ m/title="(.*?)"/is;
    my $title = $1;
    $attributes =~ m/subject="(.*?)"/is;
    my $subject = $1;
    $attributes =~ m/archive="(.*?)"/is;
    my $archive = $1;
    $attributes =~ m/posts="(.*?)"/is;
    my $posts = $1;
    $sec =~ m/<topic>(.*?)<\/topic>/is;
    my $topic = $1;
    $sec =~ m/<\/topic>(.*)<\/section>/is;
    my $content = $1;
    print qq~
    <hr width="550" color="black">
    Title:<br>
    <input name="title$c" value="$title" size="80"><br>
    Subject: <br> 
    <input name="subject$c" value="$subject" size="80"><br>
    Archive URL:<br>
    <input name="archive$c" value="$archive" size="80"><br>
    Posts:<br>
    <input name="posts$c" value="$posts" size="80"><br>
    Topic:<br>
    <input name="topic$c" value="$topic" size="80"></br>
    Content:<br>
    <Textarea rows="25" cols="150" name="content$c">$content</textarea><br>
    ~;
    $c++;
  }
  print qq~
    <hr width="550" color="black">
    New Section:<br>
    Title:<br>
    <input name="title$c" size="80"><br>
    Subject: <br> 
    <input name="subject$c" size="80"><br>
    Archive URL:<br>
    <input name="archive$c" size="80"><br>
    Posts:<br>
    <input name="posts$c" size="80"><br>
    Topic:<br>
    <input name="topic$c" size="80"></br>
    Content:<br>
    <Textarea rows="25" cols="150" name="content$c"></textarea><br>
    
  ~;
  print qq~<input type=hidden name="count" value="$c">~;
  print qq~<br><input type=submit value="Edit WWN $FORM{wwn}">~;
}

sub create_wwn{
  my $ending = ending($FORM{wwn});
  mkdir("$WWN_DIR/$FORM{wwn}") || do { print "Couldn't make new wwndir $WWN_DIR/$FORM{wwn} $!"; exit; };
  print "New WWN Directory Created<Br>";
}
sub new_wwn{
  #attempt to determine new issue #
  opendir(dir,$WWN_DIR);
  my @dirs=readdir(dir);
  close(dir); 
  my $max;
  foreach(@dirs){
    if($_ > $max){ $max = $_; }
  }
  my $last = $max;
  $max++;


  print qq~
   <form action="$0?a=create" method="POST">
   Issue Number:<br>
   <input name="wwn" value="$max"><br>
   <input type="Submit" value="Create new WWN">
 ~;
}


sub chose_wwn{
  print qq~<a href="$0?a=new">Make new WWN</a><br>~;
  opendir(dir,$WWN_DIR);
  my @dirs = readdir(dir);
  close(dir); 
  @dirs = grep (!/^\.*$/,@dirs);
  @dirs = grep (!/svn/,@dirs);
  foreach(reverse sort @dirs){
    print qq~<a href="$0?a=edit&wwn=$_">$_</a> (<a href="http://home.bluesata.com/WineWWN/WineHQ/?issue=$_">View</a>)<br>~;
  }
}

sub parse_form{
    read( STDIN, $buffer, $ENV{CONTENT_LENGTH} );
    $buffer .= "&".$ENV{QUERY_STRING};
    @pairs = split ( /&/, $buffer );
    foreach $pair (@pairs) {
        ( $name, $value ) = split ( /=/, $pair );
        $value =~ tr/+/ /;
        $value =~ s/%([a-fA-F0-9][a-fA-F0-9])/pack("C", hex($1))/eg;
        $FORM{$name} = $value;
  }
}

sub ending{
  my $num = $_[0];
  $num = $num % 10;
  if($num == 0){
    return "th";
  }elsif($num == 1){ 
    return "st";
  }elsif($num == 2){ 
    return "nd";
  }elsif($num == 3){ 
    return "rd";
  }elsif($num == 4){ 
    return "th";
  }elsif($num == 5){ 
    return "th";
  }elsif($num == 6){ 
    return "th";
  }elsif($num == 7){ 
    return "th";
  }elsif($num == 8){
    return "th";
  }elsif($num == 9){ 
    return "th";  
  }
}
