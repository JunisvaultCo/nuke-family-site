function show()
{
  document.getElementById("dropdown").classList.toggle("show");
  
}
function showFaction(id)
{
    document.getElementById("image").innerHTML='<img src="factionimg.php?factionid='+id+'">';
}
