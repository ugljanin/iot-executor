function LoadX()
    print("------LoadX------")
    s = {ssid="", pwd="", broker="", domain="", err="",boot="",port=""}
    if (file.open("config.txt","r")) then
        local sF = file.read()
        --print("setting: "..sF)
        file.close()
        for k, v in string.gmatch(sF, "([%w.]+)=([%S ]+)") do
            s[k] = v
            print(k .. ": " .. v)
        end
    end
end

function SaveX(sErr)
    if (sErr) then
        s.err = sErr
    end
    file.remove("config.txt")
    file.open("config.txt","w+")
    for k, v in pairs(s) do
        file.writeline(k .. "=" .. v)
    end
    file.close()
    collectgarbage()
end

-- If there is a script pending for update stop previously running script
function checkIfScriptUpdateIsPending()
    print("Checking if there is an update pending.")

    -- Check if there is some update pending
    http.get("http://"..s.domain.."/node.php?id="..id.."&update", nil, function(code, data)
        if (code < 0) then
            print("HTTP request failed")
            node.restart()
        else
            if string.find(data, "UPDATE")~=nil then
                print("ako ima UPDARE http://"..s.domain.."/node.php?id="..id.."&update")
                downloadAndCompile();
            else
                if (s.boot~="") then
                    print("Booting in initial state.")
                    dofile(s.boot)
                else
                    print("Initial script is missing, check if the update is set to Force update in the dashboard.")
                    node.restart()
                end
            end
            data = nil
        end
    end)
end

function downloadAndCompile()
    print("Mutation code downloading initiated.")

    -- Delete old boot file.
    filename = "boot.lua"
    file.remove(filename);
    file.open(filename, "w+")

    -- Download new mutation code, it must be set to Force update in the dashboard.
    http.get("http://"..s.domain.."/uploads/"..id.."/"..filename.."", nil, function(code, data)
        if (code < 0) then
            print("HTTP request failed")
        else
            -- Write new mutation code to the file.
            file.write(data)
            file.flush()
            file.close()

            print("+++++++++")
            print("Compiling new script...")
            print("+++++++++")

            -- Compile file to bootable format.
            ext = string.sub(filename, -3)
            if (ext == "lua") then
                node.compile(filename)
            end
            -- Save path for startup script
            s.boot = "boot.lc"
            SaveX("No error")
            node.restart()
        end
    end)
end

id = node.chipid()
print ("nodeID is: "..id)

LoadX()

if (s.broker~="") then
    wifi.setmode (wifi.STATION)
    station_cfg={}
    station_cfg.ssid=s.ssid
    station_cfg.pwd=s.pwd
    station_cfg.save=true
    wifi.sta.config(station_cfg)
    wifi.sta.autoconnect (1)

    iFail = 20 -- trying to connect to AP in 20sec, if not then reboot
    local mytimer = tmr.create()

    mytimer:register(1000, 1, function (t)
        iFail = iFail -1
        if (iFail == 0) then
            SaveX("Could not access WiFi: Zzz"..s.ssid)
            node.restart()
        end

        if wifi.sta.getip ( ) == nil then
            print(s.ssid..": "..iFail)
        else
            print ("Init NodeMCU IP: " .. wifi.sta.getip())

            m = mqtt.Client(id, 120, s.mqttuser, s.mqttpass)

            -- Checking if there is script available when running for the first time
            m:connect(s.broker, s.port, false , function(conn)
                print("Checking if there is script available when running for the first time")
                print("Connecting to MQTT broker.")
                m:subscribe("/mutation/update", 0, function(conn)
                    print("Successfully subscribed to MQTT broker.")
                    checkIfScriptUpdateIsPending()
                end)

            end)

            -- React on the request from IoT executor
            m:on("message", function(conn, topic, data)
                print("Message is received from IoT executor while the device is running")
                if data ~= nil then
                    if tonumber(data) == id then
                        print("-----------")
                        print("Stoping previously running script and restarting the device on message ...")
                        print("-----------")

                        s.boot=nil
                        SaveX()
                        node.restart()
                    end
                end
            end)

            -- tmr.stop (1)
            t:unregister()
        end
    end)

    mytimer:start()

else
    print("Please add all the parameters in config.txt file")
end
