function checkInput()
{
    // make sure the required inputs are there
    if (document.getElementById('mFirst').value == "" || 
        document.getElementById('mLast').value == "" || 
        document.getElementById('mEmail').value == "" || 
        document.getElementById('mMobile').value == "")
    {
        document.getElementById("mResult").innerHTML="<span style='color:red;'>Must supply first name, last name, email, and mobile.</span>";  
        return false;
    }
    else
    {
        document.getElementById("mResult").innerHTML = "";       
    }    
    return true;
} 