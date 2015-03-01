      window.addEventListener('load',function fixBootstrapButtonInput() {
        window.removeEventListener('load', fixBootstrapButtonInput, false);
        var __ucl_btnGroupList = document.getElementsByClassName("btn-group");
        for(var i = 0; i < __ucl_btnGroupList.length; i++)
        {
          __ucl_btnGroupList[i].setAttribute("data-toggle", "buttons");
        }
        
        var __ucl_btnDefaultList = document.getElementsByClassName("btn-default");
        for(var i = 0; i < __ucl_btnDefaultList.length; i++)
        {
          if(__ucl_btnDefaultList[i].children.length)
          {
            var children = __ucl_btnDefaultList[i].children;
            for(var j = 0; j < children.length; j++)
            {
              if(children[j].hasAttribute("checked")) {
                __ucl_btnDefaultList[i].className = __ucl_btnDefaultList[i].className + " active";
                j = children.length;
              }
            }
          }
        }
        
      },false);
