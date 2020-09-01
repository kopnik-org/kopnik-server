FROM nginx:1.19.2-alpine

#ARG APP_ENV

COPY ./nginx.conf /etc/nginx/conf.d/default.conf

#CMD sed -i.bak s#/app/var/log/#/app/var/log/$env_#g /etc/nginx/conf.d/default.conf && nginx -g "daemon off;"
#CMD sed -i s/APP_ENV/YAHOO/g /etc/nginx/conf.d/default.conf

#CMD nginx -g "daemon off;"

#CMD cat /etc/nginx/conf.d/default.conf > /app/var/log/default_ginx.conf

#RUN echo ${APP_ENV} > /app/var/log/env.conf

#COPY /app/var/log/default_ginx.conf /app/var/log/default_ginx.conf2

#CMD sed -i s/APP_ENV/YAHOO/g /app/var/log/default_ginx.conf2

#WORKDIR /app
