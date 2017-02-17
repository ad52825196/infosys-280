function errorinfield(whichcontrol, errormsg)
{
    alert(errormsg);
    whichcontrol.focus();
    whichcontrol.select();
    return false;
}

function validateint(whichcontrol)
{
    var whatvalue = parseInt(whichcontrol.value);
    if (whichcontrol.value.length == 0)
        return true;
    if (isNaN(whatvalue))
        return errorinfield(whichcontrol, "This is not a number!");
    if (whatvalue != whichcontrol.value)
        return errorinfield(whichcontrol, "There are non-numeric elements in the value!");
    return true;
}

function validatefloat(whichcontrol)
{
    var whatvalue = parseFloat(whichcontrol.value);
    if (whichcontrol.value.length == 0)
        return true;
    if (isNaN(whatvalue))
        return errorinfield(whichcontrol, "This is not a number!");
    if (whatvalue != whichcontrol.value)
        return errorinfield(whichcontrol, "There are non-numeric elements in the value!");
    return true;
}