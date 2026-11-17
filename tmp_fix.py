path = r'C:\xampp\htdocs\dost-cms\Final.jmx'
with open(path, 'r', encoding='utf-8') as f:
    s = f.read()
start = s.find('testname="Update Test User"')
end = s.find('testname="Delete Test User"', start)
block = s[start:end]
old_u = '''              <elementProp name="username" elementType="HTTPArgument">
                <boolProp name="HTTPArgument.always_encode">false</boolProp>
                <stringProp name="Argument.value">jmeter_test_</stringProp>'''
new_u = '''              <elementProp name="username" elementType="HTTPArgument">
                <boolProp name="HTTPArgument.always_encode">false</boolProp>
                <stringProp name="Argument.value">jmeter_test_</stringProp>'''
if old_u in block:
    block = block.replace(old_u, new_u)
old_e = '''              <elementProp name="employee_name" elementType="HTTPArgument">
                <boolProp name="HTTPArgument.always_encode">false</boolProp>
                <stringProp name="Argument.value">Jmeter Test User Updated </stringProp>'''
new_e = '''              <elementProp name="employee_name" elementType="HTTPArgument">
                <boolProp name="HTTPArgument.always_encode">false</boolProp>
                <stringProp name="Argument.value">Jmeter Test User Updated </stringProp>'''
if old_e in block:
    block = block.replace(old_e, new_e)
s2 = s[:start] + block + s[end:]
with open(path, 'w', encoding='utf-8') as f:
    f.write(s2)
print('done')
